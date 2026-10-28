<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/payment_service.php';
require_once dirname(__DIR__) . '/data/site.php';

bamstay_start_session();
$user = bamstay_require_portal_user(true);
$pdo = bamstay_database();
$today = (new DateTimeImmutable('today'))->format('Y-m-d');

function payment_event(PDO $pdo, string $paymentId, int $actorId, string $action, ?array $before, ?array $after): void
{
    $statement = $pdo->prepare(
        'INSERT INTO payment_events (payment_id, actor_id, action, before_state, after_state) VALUES (:payment_id, :actor_id, :action, :before_state, :after_state)'
    );
    $statement->execute([
        ':payment_id' => $paymentId,
        ':actor_id' => $actorId,
        ':action' => $action,
        ':before_state' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
        ':after_state' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR),
    ]);
}

function payment_snapshot(array $row): array
{
    return [
        'amountReceived' => (string) $row['amount_received'],
        'paymentMethod' => (string) $row['payment_method'],
        'referenceNumber' => $row['reference_number'],
        'paymentDate' => (string) $row['payment_date'],
        'status' => (string) $row['status'],
    ];
}

function payment_record(PDO $pdo, string $paymentId, ?int $tenantId, bool $lock = false): array|false
{
    $query = "SELECT p.*, o.tenant_id, o.billing_month, o.amount_due, o.late_fee, o.due_date,
                t.full_name, t.email, t.room_id, t.bedspace_id, creator.email AS creator_email
              FROM payments p
              JOIN rental_obligations o ON o.id = p.obligation_id
              JOIN tenants t ON t.id = o.tenant_id
              JOIN admin_users creator ON creator.id = p.created_by
              WHERE p.id = :payment_id";
    if ($tenantId !== null) {
        $query .= ' AND o.tenant_id = :tenant_id';
    }
    if ($lock) {
        $query .= ' FOR UPDATE';
    }
    $statement = $pdo->prepare($query);
    $parameters = [':payment_id' => $paymentId];
    if ($tenantId !== null) {
        $parameters[':tenant_id'] = $tenantId;
    }
    $statement->execute($parameters);
    return $statement->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $operation = $_GET['op'] ?? 'list';
    if ($operation === 'quote') {
        $tenantId = $user['role'] === 'admin'
            ? filter_var($_GET['tenantId'] ?? null, FILTER_VALIDATE_INT)
            : $user['tenant_id'];
        $month = is_string($_GET['month'] ?? null) ? bamstay_month_start($_GET['month']) : null;
        if (!$tenantId || $month === null) {
            bamstay_json(['error' => 'Choose an active tenant and a valid billing month.'], 422);
        }
        $tenantQuery = $pdo->prepare("SELECT id, monthly_rent, move_in_date FROM tenants WHERE id = :id AND status = 'active'");
        $tenantQuery->execute([':id' => $tenantId]);
        $tenant = $tenantQuery->fetch();
        if ($tenant === false) {
            bamstay_json(['error' => 'That tenant account is inactive or no longer exists.'], 404);
        }
        $obligationQuery = $pdo->prepare('SELECT id, amount_due, late_fee, due_date FROM rental_obligations WHERE tenant_id = :tenant_id AND billing_month = :billing_month');
        $obligationQuery->execute([':tenant_id' => $tenantId, ':billing_month' => $month]);
        $obligation = $obligationQuery->fetch();
        $rentDue = bamstay_parse_amount((string) ($obligation['amount_due'] ?? $tenant['monthly_rent'])) ?? 0;
        $lateFee = bamstay_parse_amount((string) ($obligation['late_fee'] ?? '0.00')) ?? 0;
        $confirmed = 0;
        $pending = 0;
        $pendingSubmissions = 0;
        if ($obligation !== false) {
            $aggregate = $pdo->prepare(
                "SELECT
                    COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount_received ELSE 0 END), 0) AS confirmed,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN amount_received ELSE 0 END), 0) AS pending
                 FROM payments WHERE obligation_id = :obligation_id"
            );
            $aggregate->execute([':obligation_id' => (int) $obligation['id']]);
            $totals = $aggregate->fetch();
            $confirmed = bamstay_parse_amount((string) $totals['confirmed']) ?? 0;
            $pending = bamstay_parse_amount((string) $totals['pending']) ?? 0;
        }
        $submissionQuery = $pdo->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM payment_submissions
             WHERE tenant_id = :tenant_id AND billing_month = :billing_month AND status = 'pending'"
        );
        $submissionQuery->execute([':tenant_id' => $tenantId, ':billing_month' => $month]);
        $pendingSubmissions = bamstay_parse_amount((string) $submissionQuery->fetchColumn()) ?? 0;
        $previousQuery = $pdo->prepare(
            "SELECT COALESCE(SUM(GREATEST(0, o.amount_due + o.late_fee - COALESCE(applied.total, 0))), 0)
             FROM rental_obligations o
             LEFT JOIN (
                SELECT obligation_id, SUM(amount_received) AS total FROM payments WHERE status = 'confirmed' GROUP BY obligation_id
             ) applied ON applied.obligation_id = o.id
             WHERE o.tenant_id = :tenant_id AND o.billing_month < :billing_month"
        );
        $previousQuery->execute([':tenant_id' => $tenantId, ':billing_month' => $month]);
        $previousBalance = bamstay_parse_amount((string) $previousQuery->fetchColumn()) ?? 0;
        $totalDue = $rentDue + $lateFee;
        bamstay_json([
            'tenantId' => $tenantId,
            'billingMonth' => $month,
            'earliestBillingMonth' => (new DateTimeImmutable((string) $tenant['move_in_date']))->modify('first day of this month')->format('Y-m'),
            'rentDue' => bamstay_money($rentDue),
            'lateFee' => bamstay_money($lateFee),
            'dueDate' => $obligation['due_date'] ?? $month,
            'previousBalance' => bamstay_money($previousBalance),
            'confirmedPayments' => bamstay_money($confirmed),
            'pendingPayments' => bamstay_money($pending),
            'remainingBalance' => bamstay_money(max(0, $totalDue - $confirmed)),
            'availableToRecord' => bamstay_money(max(0, $totalDue - $confirmed - $pending - $pendingSubmissions)),
        ]);
    }
    if ($operation === 'submissions') {
        $conditions = [];
        $parameters = [];
        if ($user['role'] === 'tenant') {
            $conditions[] = 's.tenant_id = :tenant_id';
            $parameters[':tenant_id'] = $user['tenant_id'];
        }
        $status = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
        if ($status !== '' && !in_array($status, ['pending', 'accepted', 'declined'], true)) {
            bamstay_json(['error' => 'Choose a valid submission status.'], 422);
        }
        if ($status !== '') {
            $conditions[] = 's.status = :status';
            $parameters[':status'] = $status;
        }
        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $statement = $pdo->prepare(
            "SELECT s.id, s.tenant_id, s.billing_month, s.amount, s.payment_method, s.reference_number,
                    s.payment_date, s.status, s.reviewed_at, s.review_note, s.created_at,
                    s.reviewed_by, t.full_name, t.room_id, t.bedspace_id, reviewer.email AS reviewer_email
             FROM payment_submissions s
             JOIN tenants t ON t.id = s.tenant_id
             LEFT JOIN admin_users reviewer ON reviewer.id = s.reviewed_by
             {$where}
             ORDER BY s.created_at DESC LIMIT 300"
        );
        $statement->execute($parameters);
        $submissions = array_map(static fn (array $row): array => [
            'id' => $row['id'],
            'tenantId' => (int) $row['tenant_id'],
            'tenantName' => $row['full_name'],
            'roomId' => $row['room_id'],
            'bedspaceId' => $row['bedspace_id'],
            'billingMonth' => $row['billing_month'],
            'amount' => (string) $row['amount'],
            'paymentMethod' => $row['payment_method'],
            'referenceNumber' => $row['reference_number'],
            'paymentDate' => $row['payment_date'],
            'status' => ucfirst($row['status']),
            'createdAt' => $row['created_at'],
            'reviewedAt' => $row['reviewed_at'],
            'reviewedBy' => $row['reviewer_email'],
            'reviewNote' => $row['review_note'],
        ], $statement->fetchAll());
        bamstay_json(['submissions' => $submissions]);
    }
    if ($operation === 'report') {
        if ($user['role'] !== 'admin') {
            bamstay_json(['error' => 'Reports are available to administrators only.'], 403);
        }
        $fromMonth = is_string($_GET['from'] ?? null) ? bamstay_month_start($_GET['from']) : null;
        $toMonth = is_string($_GET['to'] ?? null) ? bamstay_month_start($_GET['to']) : null;
        if ($fromMonth === null || $toMonth === null || $fromMonth > $toMonth) {
            bamstay_json(['error' => 'Choose a valid date range with the start month no later than the end month.'], 422);
        }
        $rangeStart = new DateTimeImmutable($fromMonth);
        $rangeEnd = new DateTimeImmutable($toMonth);
        if ($rangeEnd->diff($rangeStart)->y * 12 + $rangeEnd->diff($rangeStart)->m > 35) {
            bamstay_json(['error' => 'Report date ranges may not exceed 36 months.'], 422);
        }
        $monthlyQuery = $pdo->prepare(
            "SELECT o.billing_month, o.due_date,
                    SUM(o.amount_due + o.late_fee) AS expected,
                    SUM(COALESCE(applied.confirmed, 0)) AS collected,
                    SUM(CASE WHEN o.due_date >= :today_pending THEN GREATEST(0, o.amount_due + o.late_fee - COALESCE(applied.confirmed, 0)) ELSE 0 END) AS pending,
                    SUM(CASE WHEN o.due_date < :today_overdue AND o.amount_due + o.late_fee > COALESCE(applied.confirmed, 0) THEN GREATEST(0, o.amount_due + o.late_fee - COALESCE(applied.confirmed, 0)) ELSE 0 END) AS overdue,
                    SUM(CASE WHEN o.amount_due + o.late_fee <= COALESCE(applied.confirmed, 0) THEN 1 ELSE 0 END) AS paid_count,
                    SUM(CASE WHEN o.amount_due + o.late_fee > COALESCE(applied.confirmed, 0) AND o.due_date >= :today_pending_count AND COALESCE(applied.pending, 0) > 0 THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN o.amount_due + o.late_fee > COALESCE(applied.confirmed, 0) AND o.due_date < :today_status THEN 1 ELSE 0 END) AS overdue_count,
                    SUM(CASE WHEN o.amount_due + o.late_fee > COALESCE(applied.confirmed, 0) AND COALESCE(applied.confirmed, 0) > 0 AND o.due_date >= :today_partial AND COALESCE(applied.pending, 0) = 0 THEN 1 ELSE 0 END) AS partial_count
             FROM rental_obligations o
             LEFT JOIN (
                SELECT obligation_id,
                    SUM(CASE WHEN status = 'confirmed' THEN amount_received ELSE 0 END) AS confirmed,
                    SUM(CASE WHEN status = 'pending' THEN amount_received ELSE 0 END) AS pending
                FROM payments GROUP BY obligation_id
             ) applied ON applied.obligation_id = o.id
             WHERE o.billing_month >= :from_month AND o.billing_month <= :to_month
             GROUP BY o.billing_month, o.due_date ORDER BY o.billing_month"
        );
        $monthlyQuery->execute([
            ':today_pending' => $today,
            ':today_overdue' => $today,
            ':today_pending_count' => $today,
            ':today_status' => $today,
            ':today_partial' => $today,
            ':from_month' => $fromMonth,
            ':to_month' => $toMonth,
        ]);
        $monthly = [];
        $totals = ['expected' => 0, 'collected' => 0, 'pending' => 0, 'overdue' => 0];
        $counts = ['paid' => 0, 'pending' => 0, 'overdue' => 0, 'partial' => 0];
        foreach ($monthlyQuery->fetchAll() as $row) {
            $expected = bamstay_parse_amount((string) $row['expected']) ?? 0;
            $collected = bamstay_parse_amount((string) $row['collected']) ?? 0;
            $pending = bamstay_parse_amount((string) $row['pending']) ?? 0;
            $overdue = bamstay_parse_amount((string) $row['overdue']) ?? 0;
            $totals['expected'] += $expected;
            $totals['collected'] += $collected;
            $totals['pending'] += $pending;
            $totals['overdue'] += $overdue;
            $counts['paid'] += (int) $row['paid_count'];
            $counts['pending'] += (int) $row['pending_count'];
            $counts['overdue'] += (int) $row['overdue_count'];
            $counts['partial'] += (int) $row['partial_count'];
            $monthly[] = [
                'month' => $row['billing_month'],
                'expected' => bamstay_money($expected),
                'collected' => bamstay_money($collected),
                'pending' => bamstay_money($pending),
                'overdue' => bamstay_money($overdue),
                'collectionRate' => $expected > 0 ? round($collected / $expected * 100, 1) : 0,
            ];
        }
        $activeTenants = (int) $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'active'")->fetchColumn();
        $totalBeds = 0;
        foreach ($rooms as $room) {
            $totalBeds += count($room['bedspaces']);
        }
        $summary = bamstay_payment_summary($pdo);
        bamstay_json([
            'monthly' => $monthly,
            'totals' => [
                'expected' => bamstay_money($totals['expected']),
                'collected' => bamstay_money($totals['collected']),
                'pending' => bamstay_money($totals['pending']),
                'overdue' => bamstay_money($totals['overdue']),
                'collectionRate' => $totals['expected'] > 0 ? round($totals['collected'] / $totals['expected'] * 100, 1) : 0,
            ],
            'obligations' => $counts,
            'occupancy' => ['activeTenants' => $activeTenants, 'totalBeds' => $totalBeds, 'rate' => $totalBeds > 0 ? round($activeTenants / $totalBeds * 100, 1) : 0],
            'summary' => $summary,
        ]);
    }
    if ($operation === 'receipt') {
        $id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
        if (!preg_match('/^[a-f0-9-]{36}$/iD', $id)) {
            bamstay_json(['error' => 'Invalid receipt identifier.'], 400);
        }
        $receipt = payment_record($pdo, $id, $user['role'] === 'tenant' ? $user['tenant_id'] : null);
        if ($receipt === false) {
            bamstay_json(['error' => 'Payment receipt was not found.'], 404);
        }
        $totals = bamstay_obligation_totals($pdo, (int) $receipt['obligation_id'], (string) $receipt['due_date'], $today);
        bamstay_json([
            'receipt' => [
                'id' => $receipt['id'],
                'receiptNumber' => $receipt['receipt_number'],
                'tenantName' => $receipt['full_name'],
                'roomId' => $receipt['room_id'],
                'bedspaceId' => $receipt['bedspace_id'],
                'billingMonth' => $receipt['billing_month'],
                'rentDue' => (string) $receipt['amount_due'],
                'lateFee' => (string) $receipt['late_fee'],
                'amountReceived' => (string) $receipt['amount_received'],
                'paymentMethod' => $receipt['payment_method'],
                'referenceNumber' => $receipt['reference_number'],
                'paymentDate' => $receipt['payment_date'],
                'dueDate' => $receipt['due_date'],
                'remainingBalance' => bamstay_money($totals['remaining']),
                'status' => match ($receipt['status']) {
                    'pending' => 'Pending',
                    'reversed' => 'Reversed',
                    'void' => 'Void',
                    default => $totals['status'],
                },
                'createdAt' => $receipt['created_at'],
                    'processedBy' => $receipt['creator_email'],
            ],
        ]);
    }
    if ($operation !== 'list') {
        bamstay_json(['error' => 'Unknown payment operation.'], 400);
    }

    $conditions = [];
    $parameters = [];
    if ($user['role'] === 'tenant') {
        $conditions[] = 'o.tenant_id = :tenant_id';
        $parameters[':tenant_id'] = $user['tenant_id'];
    }
    $search = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
    if ($search !== '') {
        $conditions[] = "(t.full_name LIKE :query OR p.receipt_number LIKE :query_receipt OR p.reference_number LIKE :query_reference OR DATE_FORMAT(o.billing_month, '%Y-%m') LIKE :query_month)";
        $parameters[':query'] = '%' . $search . '%';
        $parameters[':query_receipt'] = '%' . $search . '%';
        $parameters[':query_reference'] = '%' . $search . '%';
        $parameters[':query_month'] = '%' . $search . '%';
    }
    $month = is_string($_GET['month'] ?? null) ? $_GET['month'] : '';
    if ($month !== '') {
        $monthStart = bamstay_month_start($month);
        if ($monthStart === null) {
            bamstay_json(['error' => 'Choose a valid billing month.'], 422);
        }
        $conditions[] = 'o.billing_month = :billing_month';
        $parameters[':billing_month'] = $monthStart;
    }
    $method = is_string($_GET['method'] ?? null) ? $_GET['method'] : '';
    if ($method !== '') {
        if (!in_array($method, ['Cash', 'GCash', 'Maya', 'Bank Transfer', 'Other'], true)) {
            bamstay_json(['error' => 'Choose a valid payment method.'], 422);
        }
        $conditions[] = 'p.payment_method = :method';
        $parameters[':method'] = $method;
    }
    $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
    $query = "SELECT p.id, p.receipt_number, p.amount_received, p.payment_method, p.reference_number,
                    p.payment_date, p.status AS payment_status, p.created_at,
                    o.id AS obligation_id, o.billing_month, o.amount_due, o.late_fee, o.due_date,
                    t.id AS tenant_id, t.full_name, t.room_id, t.bedspace_id,
                    COALESCE(SUM(CASE WHEN applied.status = 'confirmed' THEN applied.amount_received ELSE 0 END), 0) AS confirmed_total,
                    COALESCE(SUM(CASE WHEN applied.status = 'pending' THEN applied.amount_received ELSE 0 END), 0) AS pending_total
             FROM payments p
             JOIN rental_obligations o ON o.id = p.obligation_id
             JOIN tenants t ON t.id = o.tenant_id
             LEFT JOIN payments applied ON applied.obligation_id = o.id
             {$where}
             GROUP BY p.id, p.receipt_number, p.amount_received, p.payment_method, p.reference_number,
                      p.payment_date, p.status, p.created_at, o.id, o.billing_month, o.amount_due,
                      o.late_fee, o.due_date, t.id, t.full_name, t.room_id, t.bedspace_id
             ORDER BY p.created_at DESC LIMIT 500";
    $statement = $pdo->prepare($query);
    $statement->execute($parameters);
    $records = [];
    foreach ($statement->fetchAll() as $row) {
        $totalDue = (bamstay_parse_amount((string) $row['amount_due']) ?? 0)
            + (bamstay_parse_amount((string) $row['late_fee']) ?? 0);
        $confirmed = bamstay_parse_amount((string) $row['confirmed_total']) ?? 0;
        $pending = bamstay_parse_amount((string) $row['pending_total']) ?? 0;
        $balance = max(0, $totalDue - $confirmed);
        if ($balance === 0) {
            $obligationStatus = 'Paid';
        } elseif ($row['due_date'] < $today) {
            $obligationStatus = 'Overdue';
        } elseif ($pending > 0) {
            $obligationStatus = 'Pending';
        } elseif ($confirmed > 0) {
            $obligationStatus = 'Partially Paid';
        } else {
            $obligationStatus = 'Due';
        }
        $paymentStatus = (string) $row['payment_status'];
        $displayStatus = match ($paymentStatus) {
            'pending' => 'Pending',
            'reversed' => 'Reversed',
            'void' => 'Void',
            default => $balance === 0 ? 'Paid' : ($row['due_date'] < $today ? 'Overdue' : ($confirmed > 0 ? 'Partially Paid' : ($pending > 0 ? 'Pending' : 'Due'))),
        };
        $records[] = [
            'id' => $row['id'],
            'receiptNumber' => $row['receipt_number'],
            'tenantId' => (int) $row['tenant_id'],
            'tenantName' => $row['full_name'],
            'roomId' => $row['room_id'],
            'bedspaceId' => $row['bedspace_id'],
            'billingMonth' => $row['billing_month'],
            'amountDue' => bamstay_money($totalDue),
            'amountReceived' => (string) $row['amount_received'],
            'remainingBalance' => bamstay_money($balance),
            'paymentMethod' => $row['payment_method'],
            'referenceNumber' => $row['reference_number'],
            'paymentDate' => $row['payment_date'],
            'dueDate' => $row['due_date'],
            'status' => ucfirst($paymentStatus),
            'obligationStatus' => $obligationStatus,
            'displayStatus' => $displayStatus,
            'createdAt' => $row['created_at'],
            'pendingTotal' => bamstay_money($pending),
            'canVerify' => $user['role'] === 'admin' && $paymentStatus === 'pending',
            'canReverse' => $user['role'] === 'admin' && $paymentStatus === 'confirmed',
            'canVoid' => $user['role'] === 'admin' && $paymentStatus === 'pending',
            'canEdit' => $user['role'] === 'admin' && $paymentStatus === 'pending',
        ];
    }

    $filterStatus = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
    if ($filterStatus !== '' && !in_array($filterStatus, ['Paid', 'Pending', 'Partially Paid', 'Overdue', 'Due', 'Reversed', 'Void'], true)) {
        bamstay_json(['error' => 'Choose a valid payment status.'], 422);
    }
    if ($filterStatus !== '') {
        $records = array_values(array_filter($records, static fn (array $record): bool => $record['displayStatus'] === $filterStatus));
    }

    $summary = null;
    if ($user['role'] === 'admin') {
        $summary = bamstay_payment_summary($pdo);
    } else {
        $summaryQuery = $pdo->prepare(
            "SELECT
                (SELECT COALESCE(SUM(amount_received), 0) FROM payments
                    WHERE status = 'confirmed' AND payment_date >= :month_start AND payment_date < :next_month
                    AND obligation_id IN (SELECT id FROM rental_obligations WHERE tenant_id = :collected_tenant)) AS collected,
                COALESCE(SUM(GREATEST(0, o.amount_due + o.late_fee - COALESCE(paid.total, 0))), 0) AS outstanding,
                COALESCE(SUM(CASE WHEN o.due_date < :today AND o.amount_due + o.late_fee > COALESCE(paid.total, 0) THEN GREATEST(0, o.amount_due + o.late_fee - COALESCE(paid.total, 0)) ELSE 0 END), 0) AS overdue
             FROM rental_obligations o
             LEFT JOIN (
                SELECT obligation_id, SUM(amount_received) AS total FROM payments WHERE status = 'confirmed' GROUP BY obligation_id
             ) paid ON paid.obligation_id = o.id
             WHERE o.tenant_id = :tenant_id
             GROUP BY o.tenant_id"
        );
        $start = (new DateTimeImmutable('first day of this month'))->format('Y-m-d');
        $next = (new DateTimeImmutable('first day of next month'))->format('Y-m-d');
        $summaryQuery->execute([':month_start' => $start, ':next_month' => $next, ':collected_tenant' => $user['tenant_id'], ':today' => $today, ':tenant_id' => $user['tenant_id']]);
        $tenantSummary = $summaryQuery->fetch() ?: ['collected' => '0.00', 'outstanding' => '0.00', 'overdue' => '0.00'];
        $summary = [
            'totalCollected' => (string) $tenantSummary['collected'],
            'outstandingBalance' => (string) $tenantSummary['outstanding'],
            'overdueAmount' => (string) $tenantSummary['overdue'],
            'pendingCount' => count(array_filter($records, static fn (array $record): bool => $record['status'] === 'Pending')),
        ];
    }
    bamstay_json(['records' => $records, 'summary' => $summary, 'role' => $user['role']]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    bamstay_json(['error' => 'Method not allowed.'], 405);
}
bamstay_check_csrf();
$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input) || !is_string($input['action'] ?? null)) {
    bamstay_json(['error' => 'Invalid request data.'], 400);
}
$action = $input['action'];

if ($user['role'] === 'tenant') {
    if ($action !== 'submit-tenant-payment') {
        bamstay_json(['error' => 'Tenants can only submit payment requests.'], 403);
    }
    $billingMonth = is_string($input['billingMonth'] ?? null) ? bamstay_month_start($input['billingMonth']) : null;
    $amountCents = bamstay_parse_amount($input['amount'] ?? null);
    $method = is_string($input['paymentMethod'] ?? null) ? $input['paymentMethod'] : '';
    $reference = trim(is_string($input['referenceNumber'] ?? null) ? $input['referenceNumber'] : '');
    $paymentDate = trim(is_string($input['paymentDate'] ?? null) ? $input['paymentDate'] : '');
    $submissionId = is_string($input['submissionId'] ?? null) ? $input['submissionId'] : '';
    $qrPath = dirname(__DIR__) . '/assets/images/gcash-qr.png';
    if ($billingMonth === null) {
        bamstay_json(['error' => 'Choose a valid billing month.', 'field' => 'billingMonth'], 422);
    }
    if ($amountCents === null || $amountCents < 1) {
        bamstay_json(['error' => 'Enter an amount greater than zero with no more than two decimal places.', 'field' => 'amount'], 422);
    }
    if (!in_array($method, ['Cash', 'GCash'], true)) {
        bamstay_json(['error' => 'Choose Cash or GCash.', 'field' => 'paymentMethod'], 422);
    }
    if ($method === 'GCash' && !is_file($qrPath)) {
        bamstay_json(['error' => 'GCash payment is not available until the administrator adds the payment QR code.'], 409);
    }
    if ($method === 'GCash' && $reference === '') {
        bamstay_json(['error' => 'Enter the GCash transaction reference after sending your payment.', 'field' => 'referenceNumber'], 422);
    }
    if (mb_strlen($reference) > 100) {
        bamstay_json(['error' => 'Reference number must be 100 characters or fewer.', 'field' => 'referenceNumber'], 422);
    }
    if (!bamstay_valid_date($paymentDate) || $paymentDate > $today) {
        bamstay_json(['error' => 'Enter a valid payment date no later than today.', 'field' => 'paymentDate'], 422);
    }
    if (!preg_match('/^[a-f0-9-]{36}$/iD', $submissionId)) {
        bamstay_json(['error' => 'Refresh the payment form and try again.'], 422);
    }

    try {
        $pdo->beginTransaction();
        $tenantQuery = $pdo->prepare("SELECT id, status, monthly_rent, move_in_date FROM tenants WHERE id = :id FOR UPDATE");
        $tenantQuery->execute([':id' => $user['tenant_id']]);
        $tenant = $tenantQuery->fetch();
        if ($tenant === false || $tenant['status'] !== 'active') {
            $pdo->rollBack();
            bamstay_json(['error' => 'An active tenant account is required to submit a payment.'], 403);
        }
        $existing = $pdo->prepare('SELECT status FROM payment_submissions WHERE id = :id AND tenant_id = :tenant_id');
        $existing->execute([':id' => $submissionId, ':tenant_id' => $user['tenant_id']]);
        $existingStatus = $existing->fetchColumn();
        if ($existingStatus !== false) {
            $pdo->commit();
            bamstay_json(['submissionId' => $submissionId, 'status' => ucfirst((string) $existingStatus), 'duplicateSubmission' => true]);
        }
        if ($reference !== '') {
            $existingPayment = $pdo->prepare(
                'SELECT id FROM payments WHERE payment_method = :method AND reference_number = :reference LIMIT 1'
            );
            $existingPayment->execute([':method' => $method, ':reference' => $reference]);
            if ($existingPayment->fetchColumn() !== false) {
                $pdo->rollBack();
                bamstay_json(['error' => 'That transaction reference is already recorded as a payment.', 'field' => 'referenceNumber'], 409);
            }
        }
        $currentMonth = (new DateTimeImmutable('first day of this month'))->format('Y-m-d');
        $moveInMonth = (new DateTimeImmutable((string) $tenant['move_in_date']))->modify('first day of this month')->format('Y-m-d');
        if ($billingMonth > $currentMonth || $billingMonth < $moveInMonth) {
            $pdo->rollBack();
            bamstay_json(['error' => 'Choose a billing month from your move-in month through the current month.', 'field' => 'billingMonth'], 422);
        }
        $obligationInsert = $pdo->prepare(
            'INSERT IGNORE INTO rental_obligations (tenant_id, billing_month, amount_due, due_date) VALUES (:tenant_id, :billing_month, :amount_due, :due_date)'
        );
        $obligationInsert->execute([
            ':tenant_id' => $user['tenant_id'],
            ':billing_month' => $billingMonth,
            ':amount_due' => $tenant['monthly_rent'],
            ':due_date' => $billingMonth,
        ]);
        $obligationQuery = $pdo->prepare(
            'SELECT id, amount_due, late_fee FROM rental_obligations WHERE tenant_id = :tenant_id AND billing_month = :billing_month FOR UPDATE'
        );
        $obligationQuery->execute([':tenant_id' => $user['tenant_id'], ':billing_month' => $billingMonth]);
        $obligation = $obligationQuery->fetch();
        if ($obligation === false) {
            throw new RuntimeException('Could not establish the selected rental obligation.');
        }
        $payments = $pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount_received ELSE 0 END), 0) AS confirmed,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN amount_received ELSE 0 END), 0) AS pending
             FROM payments WHERE obligation_id = :obligation_id"
        );
        $payments->execute([':obligation_id' => (int) $obligation['id']]);
        $applied = $payments->fetch();
        $requests = $pdo->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM payment_submissions
             WHERE tenant_id = :tenant_id AND billing_month = :billing_month AND status = 'pending'"
        );
        $requests->execute([':tenant_id' => $user['tenant_id'], ':billing_month' => $billingMonth]);
        $due = (bamstay_parse_amount((string) $obligation['amount_due']) ?? 0)
            + (bamstay_parse_amount((string) $obligation['late_fee']) ?? 0);
        $confirmed = bamstay_parse_amount((string) $applied['confirmed']) ?? 0;
        $pendingPayments = bamstay_parse_amount((string) $applied['pending']) ?? 0;
        $pendingRequests = bamstay_parse_amount((string) $requests->fetchColumn()) ?? 0;
        $available = max(0, $due - $confirmed - $pendingPayments - $pendingRequests);
        if ($amountCents > $available) {
            $pdo->rollBack();
            bamstay_json([
                'error' => 'The amount exceeds the remaining balance available for this billing period.',
                'field' => 'amount',
                'remaining' => bamstay_money($available),
            ], 422);
        }
        $insert = $pdo->prepare(
            'INSERT INTO payment_submissions (id, tenant_id, billing_month, amount, payment_method, reference_number, payment_date)
             VALUES (:id, :tenant_id, :billing_month, :amount, :payment_method, :reference_number, :payment_date)'
        );
        $insert->execute([
            ':id' => $submissionId,
            ':tenant_id' => $user['tenant_id'],
            ':billing_month' => $billingMonth,
            ':amount' => bamstay_money($amountCents),
            ':payment_method' => $method,
            ':reference_number' => $reference !== '' ? $reference : null,
            ':payment_date' => $paymentDate,
        ]);
        $pdo->commit();
        bamstay_json(['submissionId' => $submissionId, 'status' => 'Pending'], 201);
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception->getCode() === '23000') {
            bamstay_json(['error' => 'That GCash reference number has already been submitted.'], 409);
        }
        error_log('BAMStay tenant payment submission failed: ' . $exception->getMessage());
        bamstay_json(['error' => 'The payment request could not be submitted. No request was recorded.'], 500);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('BAMStay tenant payment submission failed: ' . $exception->getMessage());
        bamstay_json(['error' => 'The payment request could not be submitted. No request was recorded.'], 500);
    }
}

if ($user['role'] !== 'admin') {
    bamstay_json(['error' => 'Only administrators can manage payment records.'], 403);
}

if ($action === 'review-submission') {
    $submissionId = is_string($input['submissionId'] ?? null) ? $input['submissionId'] : '';
    $decision = is_string($input['decision'] ?? null) ? $input['decision'] : '';
    $reviewNote = trim(is_string($input['reviewNote'] ?? null) ? $input['reviewNote'] : '');
    if (!preg_match('/^[a-f0-9-]{36}$/iD', $submissionId)
        || !in_array($decision, ['accept', 'decline'], true)
        || mb_strlen($reviewNote) > 500) {
        bamstay_json(['error' => 'Check the payment request review details.'], 422);
    }
    try {
        $pdo->beginTransaction();
        $tenantIdQuery = $pdo->prepare('SELECT tenant_id FROM payment_submissions WHERE id = :id');
        $tenantIdQuery->execute([':id' => $submissionId]);
        $tenantId = $tenantIdQuery->fetchColumn();
        if ($tenantId === false) {
            $pdo->rollBack();
            bamstay_json(['error' => 'Payment request was not found.'], 404);
        }
        $tenantLock = $pdo->prepare('SELECT id, status, monthly_rent FROM tenants WHERE id = :id FOR UPDATE');
        $tenantLock->execute([':id' => (int) $tenantId]);
        $tenant = $tenantLock->fetch();
        $submissionQuery = $pdo->prepare('SELECT * FROM payment_submissions WHERE id = :id FOR UPDATE');
        $submissionQuery->execute([':id' => $submissionId]);
        $submission = $submissionQuery->fetch();
        if ($submission === false || $submission['status'] !== 'pending') {
            $pdo->rollBack();
            bamstay_json(['error' => 'Only pending payment requests can be reviewed.'], 409);
        }
        if ($decision === 'decline') {
            $update = $pdo->prepare(
                "UPDATE payment_submissions SET status = 'declined', reviewed_by = :reviewer, reviewed_at = NOW(), review_note = :note
                 WHERE id = :id AND status = 'pending'"
            );
            $update->execute([
                ':reviewer' => $user['id'],
                ':note' => $reviewNote !== '' ? $reviewNote : 'Payment request was declined by the administrator.',
                ':id' => $submissionId,
            ]);
            $pdo->commit();
            bamstay_json(['success' => true, 'status' => 'Declined']);
        }
        if ($tenant === false || $tenant['status'] !== 'active') {
            $pdo->rollBack();
            bamstay_json(['error' => 'The tenant account is no longer active.'], 409);
        }
        $obligationInsert = $pdo->prepare(
            'INSERT IGNORE INTO rental_obligations (tenant_id, billing_month, amount_due, due_date) VALUES (:tenant_id, :billing_month, :amount_due, :due_date)'
        );
        $obligationInsert->execute([
            ':tenant_id' => (int) $tenantId,
            ':billing_month' => $submission['billing_month'],
            ':amount_due' => $tenant['monthly_rent'],
            ':due_date' => $submission['billing_month'],
        ]);
        $obligationQuery = $pdo->prepare(
            'SELECT id, amount_due, late_fee FROM rental_obligations WHERE tenant_id = :tenant_id AND billing_month = :billing_month FOR UPDATE'
        );
        $obligationQuery->execute([':tenant_id' => (int) $tenantId, ':billing_month' => $submission['billing_month']]);
        $obligation = $obligationQuery->fetch();
        if ($obligation === false) {
            throw new RuntimeException('The payment request obligation could not be loaded.');
        }
        $payments = $pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount_received ELSE 0 END), 0) AS confirmed,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN amount_received ELSE 0 END), 0) AS pending
             FROM payments WHERE obligation_id = :obligation_id"
        );
        $payments->execute([':obligation_id' => (int) $obligation['id']]);
        $applied = $payments->fetch();
        $otherRequests = $pdo->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM payment_submissions
             WHERE tenant_id = :tenant_id AND billing_month = :billing_month AND status = 'pending' AND id <> :submission_id"
        );
        $otherRequests->execute([
            ':tenant_id' => (int) $tenantId,
            ':billing_month' => $submission['billing_month'],
            ':submission_id' => $submissionId,
        ]);
        $due = (bamstay_parse_amount((string) $obligation['amount_due']) ?? 0)
            + (bamstay_parse_amount((string) $obligation['late_fee']) ?? 0);
        $confirmed = bamstay_parse_amount((string) $applied['confirmed']) ?? 0;
        $pendingPayments = bamstay_parse_amount((string) $applied['pending']) ?? 0;
        $reservedRequests = bamstay_parse_amount((string) $otherRequests->fetchColumn()) ?? 0;
        $amountCents = bamstay_parse_amount((string) $submission['amount']) ?? 0;
        if ($amountCents < 1 || $amountCents > max(0, $due - $confirmed - $pendingPayments - $reservedRequests)) {
            $pdo->rollBack();
            bamstay_json(['error' => 'The request no longer fits the available balance. Refresh the records and resolve other pending requests first.'], 409);
        }
        $paymentId = bamstay_uuid();
        $receiptNumber = 'BB-' . (new DateTimeImmutable())->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $paymentInsert = $pdo->prepare(
            "INSERT INTO payments (id, receipt_number, obligation_id, amount_received, payment_method, reference_number,
                payment_date, status, notes, idempotency_key, created_by)
             VALUES (:id, :receipt_number, :obligation_id, :amount, :method, :reference, :payment_date,
                'pending', :notes, :idempotency_key, :created_by)"
        );
        $paymentInsert->execute([
            ':id' => $paymentId,
            ':receipt_number' => $receiptNumber,
            ':obligation_id' => (int) $obligation['id'],
            ':amount' => bamstay_money($amountCents),
            ':method' => $submission['payment_method'],
            ':reference' => $submission['reference_number'],
            ':payment_date' => $submission['payment_date'],
            ':notes' => 'Tenant-submitted payment request reviewed and verified by admin.',
            ':idempotency_key' => $submissionId,
            ':created_by' => $user['id'],
        ]);
        $createdState = [
            'amountReceived' => bamstay_money($amountCents),
            'paymentMethod' => $submission['payment_method'],
            'referenceNumber' => $submission['reference_number'],
            'paymentDate' => $submission['payment_date'],
            'status' => 'pending',
            'submissionId' => $submissionId,
        ];
        payment_event($pdo, $paymentId, $user['id'], 'created', null, $createdState);
        $confirmPayment = $pdo->prepare(
            "UPDATE payments SET status = 'confirmed', verified_by = :reviewer, verified_at = :verified_at
             WHERE id = :id AND status = 'pending'"
        );
        $confirmPayment->execute([':reviewer' => $user['id'], ':verified_at' => $now, ':id' => $paymentId]);
        $after = [
            'amountReceived' => bamstay_money($amountCents),
            'paymentMethod' => $submission['payment_method'],
            'referenceNumber' => $submission['reference_number'],
            'paymentDate' => $submission['payment_date'],
            'status' => 'confirmed',
            'submissionId' => $submissionId,
        ];
        payment_event($pdo, $paymentId, $user['id'], 'confirmed', $createdState, $after);
        $submissionUpdate = $pdo->prepare(
            "UPDATE payment_submissions SET status = 'accepted', reviewed_by = :reviewer, reviewed_at = NOW(), review_note = :note
             WHERE id = :id AND status = 'pending'"
        );
        $submissionUpdate->execute([
            ':reviewer' => $user['id'],
            ':note' => $reviewNote !== '' ? $reviewNote : null,
            ':id' => $submissionId,
        ]);
        $pdo->commit();
        bamstay_json(['success' => true, 'status' => 'Accepted', 'paymentId' => $paymentId, 'receiptNumber' => $receiptNumber]);
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception->getCode() === '23000') {
            bamstay_json(['error' => 'That reference number is already recorded on another payment.'], 409);
        }
        error_log('BAMStay tenant payment review failed: ' . $exception->getMessage());
        bamstay_json(['error' => 'The payment request could not be reviewed.'], 500);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('BAMStay tenant payment review failed: ' . $exception->getMessage());
        bamstay_json(['error' => 'The payment request could not be reviewed.'], 500);
    }
}

if ($action === 'initialize') {
    try {
        $pdo->beginTransaction();
        bamstay_ensure_obligations($pdo);
        $pdo->commit();
        bamstay_json(['summary' => bamstay_payment_summary($pdo)]);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('BAMStay obligation initialization failed: ' . $exception->getMessage());
        bamstay_json(['error' => 'Rental obligations could not be initialized.'], 500);
    }
}

if ($action === 'create') {
    $tenantId = filter_var($input['tenantId'] ?? null, FILTER_VALIDATE_INT);
    $billingMonth = is_string($input['billingMonth'] ?? null) ? bamstay_month_start($input['billingMonth']) : null;
    $amountCents = bamstay_parse_amount($input['amountReceived'] ?? null);
    $method = is_string($input['paymentMethod'] ?? null) ? $input['paymentMethod'] : '';
    $reference = trim(is_string($input['referenceNumber'] ?? null) ? $input['referenceNumber'] : '');
    $paymentDate = trim(is_string($input['paymentDate'] ?? null) ? $input['paymentDate'] : '');
    $confirmCash = ($input['confirmCashReceived'] ?? false) === true;
    $notes = trim(is_string($input['notes'] ?? null) ? $input['notes'] : '');
    $idempotencyKey = is_string($input['idempotencyKey'] ?? null) ? $input['idempotencyKey'] : '';
    if (!$tenantId || $billingMonth === null) {
        bamstay_json(['error' => 'Select an active tenant and a valid billing month.'], 422);
    }
    if ($amountCents === null || $amountCents < 1) {
        bamstay_json(['error' => 'Amount received must be greater than zero and use no more than two decimal places.', 'field' => 'amountReceived'], 422);
    }
    if (!in_array($method, ['Cash', 'GCash', 'Maya', 'Bank Transfer', 'Other'], true)) {
        bamstay_json(['error' => 'Choose a valid payment method.', 'field' => 'paymentMethod'], 422);
    }
    if (in_array($method, ['GCash', 'Maya', 'Bank Transfer'], true) && $reference === '') {
        bamstay_json(['error' => 'A reference number is required for this payment method.', 'field' => 'referenceNumber'], 422);
    }
    if (mb_strlen($reference) > 100 || mb_strlen($notes) > 1000) {
        bamstay_json(['error' => 'Reference or notes exceed the allowed length.'], 422);
    }
    if (!bamstay_valid_date($paymentDate) || $paymentDate > $today) {
        bamstay_json(['error' => 'Payment date must be a valid date no later than today.', 'field' => 'paymentDate'], 422);
    }
    if ($confirmCash && $method !== 'Cash') {
        bamstay_json(['error' => 'Electronic payments must remain pending until an administrator verifies them.'], 422);
    }
    if (!preg_match('/^[a-f0-9-]{36}$/iD', $idempotencyKey)) {
        bamstay_json(['error' => 'Refresh the payment form and try again.'], 422);
    }

    try {
        $existing = $pdo->prepare('SELECT id, receipt_number FROM payments WHERE idempotency_key = :key');
        $existing->execute([':key' => $idempotencyKey]);
        $saved = $existing->fetch();
        if ($saved !== false) {
            bamstay_json(['id' => $saved['id'], 'receiptNumber' => $saved['receipt_number'], 'duplicateSubmission' => true]);
        }

        $pdo->beginTransaction();
        $tenant = $pdo->prepare("SELECT id, status, monthly_rent FROM tenants WHERE id = :id FOR UPDATE");
        $tenant->execute([':id' => $tenantId]);
        $tenantRow = $tenant->fetch();
        if ($tenantRow === false || $tenantRow['status'] !== 'active') {
            $pdo->rollBack();
            bamstay_json(['error' => 'That tenant account is inactive or no longer exists.'], 409);
        }
        if ($reference !== '') {
            $openSubmission = $pdo->prepare(
                "SELECT id FROM payment_submissions
                 WHERE payment_method = :method AND reference_number = :reference AND status = 'pending' LIMIT 1"
            );
            $openSubmission->execute([':method' => $method, ':reference' => $reference]);
            if ($openSubmission->fetchColumn() !== false) {
                $pdo->rollBack();
                bamstay_json(['error' => 'A tenant request already uses this reference. Review that request instead of creating a duplicate payment.'], 409);
            }
        }
        $dueDate = $billingMonth;
        $obligationInsert = $pdo->prepare(
            'INSERT IGNORE INTO rental_obligations (tenant_id, billing_month, amount_due, due_date) VALUES (:tenant_id, :billing_month, :amount_due, :due_date)'
        );
        $obligationInsert->execute([
            ':tenant_id' => $tenantId,
            ':billing_month' => $billingMonth,
            ':amount_due' => $tenantRow['monthly_rent'],
            ':due_date' => $dueDate,
        ]);
        $obligationQuery = $pdo->prepare('SELECT id, amount_due, late_fee, due_date FROM rental_obligations WHERE tenant_id = :tenant_id AND billing_month = :billing_month FOR UPDATE');
        $obligationQuery->execute([':tenant_id' => $tenantId, ':billing_month' => $billingMonth]);
        $obligation = $obligationQuery->fetch();
        if ($obligation === false) {
            throw new RuntimeException('Could not establish the selected rental obligation.');
        }
        $payments = $pdo->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount_received ELSE 0 END), 0) AS confirmed,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount_received ELSE 0 END), 0) AS pending
             FROM payments WHERE obligation_id = :obligation_id"
        );
        $payments->execute([':obligation_id' => (int) $obligation['id']]);
        $applied = $payments->fetch();
        $pendingRequestsQuery = $pdo->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM payment_submissions
             WHERE tenant_id = :tenant_id AND billing_month = :billing_month AND status = 'pending'"
        );
        $pendingRequestsQuery->execute([':tenant_id' => $tenantId, ':billing_month' => $billingMonth]);
        $totalDue = (bamstay_parse_amount((string) $obligation['amount_due']) ?? 0)
            + (bamstay_parse_amount((string) $obligation['late_fee']) ?? 0);
        $confirmed = bamstay_parse_amount((string) $applied['confirmed']) ?? 0;
        $pending = bamstay_parse_amount((string) $applied['pending']) ?? 0;
        $pendingRequests = bamstay_parse_amount((string) $pendingRequestsQuery->fetchColumn()) ?? 0;
        if ($amountCents > max(0, $totalDue - $confirmed - $pending - $pendingRequests)) {
            $pdo->rollBack();
            bamstay_json([
                'error' => 'The amount exceeds the remaining amount available for this billing period.',
                'field' => 'amountReceived',
                'remaining' => bamstay_money(max(0, $totalDue - $confirmed - $pending - $pendingRequests)),
            ], 422);
        }

        $paymentId = bamstay_uuid();
        $receiptNumber = 'BB-' . (new DateTimeImmutable())->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $status = $method === 'Cash' && $confirmCash ? 'confirmed' : 'pending';
        $referenceValue = $reference === '' ? null : $reference;
        $insert = $pdo->prepare(
            'INSERT INTO payments (id, receipt_number, obligation_id, amount_received, payment_method, reference_number, payment_date, status, notes, idempotency_key, created_by, verified_by, verified_at)
             VALUES (:id, :receipt_number, :obligation_id, :amount_received, :payment_method, :reference_number, :payment_date, :status, :notes, :idempotency_key, :created_by, :verified_by, :verified_at)'
        );
        $insert->execute([
            ':id' => $paymentId,
            ':receipt_number' => $receiptNumber,
            ':obligation_id' => (int) $obligation['id'],
            ':amount_received' => bamstay_money($amountCents),
            ':payment_method' => $method,
            ':reference_number' => $referenceValue,
            ':payment_date' => $paymentDate,
            ':status' => $status,
            ':notes' => $notes,
            ':idempotency_key' => $idempotencyKey,
            ':created_by' => $user['id'],
            ':verified_by' => $status === 'confirmed' ? $user['id'] : null,
            ':verified_at' => $status === 'confirmed' ? (new DateTimeImmutable())->format('Y-m-d H:i:s') : null,
        ]);
        $after = [
            'amountReceived' => bamstay_money($amountCents),
            'paymentMethod' => $method,
            'referenceNumber' => $referenceValue,
            'paymentDate' => $paymentDate,
            'status' => $status,
            'obligationId' => (int) $obligation['id'],
        ];
        payment_event($pdo, $paymentId, $user['id'], 'created', null, $after);
        $pdo->commit();
        bamstay_json(['id' => $paymentId, 'receiptNumber' => $receiptNumber, 'status' => ucfirst($status)], 201);
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception->getCode() === '23000') {
            bamstay_json(['error' => 'That reference number or submission has already been recorded.'], 409);
        }
        error_log('BAMStay payment creation failed: ' . $exception->getMessage());
        bamstay_json(['error' => 'The payment could not be saved. No payment was recorded.'], 500);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('BAMStay payment creation failed: ' . $exception->getMessage());
        bamstay_json(['error' => 'The payment could not be saved. No payment was recorded.'], 500);
    }
}

if (in_array($action, ['confirm', 'reverse', 'void'], true)) {
    $paymentId = is_string($input['paymentId'] ?? null) ? $input['paymentId'] : '';
    if (!preg_match('/^[a-f0-9-]{36}$/iD', $paymentId)) {
        bamstay_json(['error' => 'Invalid payment identifier.'], 400);
    }
    $reason = trim(is_string($input['reason'] ?? null) ? $input['reason'] : '');
    if ($action === 'reverse' && mb_strlen($reason) < 5) {
        bamstay_json(['error' => 'Provide a correction reason of at least five characters.', 'field' => 'reason'], 422);
    }
    try {
        $pdo->beginTransaction();
        $row = payment_record($pdo, $paymentId, null, true);
        if ($row === false) {
            $pdo->rollBack();
            bamstay_json(['error' => 'Payment record was not found.'], 404);
        }
        $before = payment_snapshot($row);
        if ($action === 'confirm') {
            if ($row['status'] !== 'pending') {
                $pdo->rollBack();
                bamstay_json(['error' => 'Only pending payments can be verified.'], 409);
            }
            $update = $pdo->prepare("UPDATE payments SET status = 'confirmed', verified_by = :actor, verified_at = NOW() WHERE id = :id AND status = 'pending'");
            $update->execute([':actor' => $user['id'], ':id' => $paymentId]);
            $event = 'confirmed';
            $after = $before;
            $after['status'] = 'confirmed';
            $after['verifiedBy'] = $user['id'];
        } elseif ($action === 'reverse') {
            if ($row['status'] !== 'confirmed') {
                $pdo->rollBack();
                bamstay_json(['error' => 'Only confirmed payments can be reversed.'], 409);
            }
            $update = $pdo->prepare("UPDATE payments SET status = 'reversed', reversed_by = :actor, reversed_at = NOW(), reversal_reason = :reason WHERE id = :id AND status = 'confirmed'");
            $update->execute([':actor' => $user['id'], ':reason' => $reason, ':id' => $paymentId]);
            $event = 'reversed';
            $after = $before;
            $after['status'] = 'reversed';
            $after['reason'] = $reason;
        } else {
            if ($row['status'] !== 'pending') {
                $pdo->rollBack();
                bamstay_json(['error' => 'Only pending payments can be voided.'], 409);
            }
            $update = $pdo->prepare("UPDATE payments SET status = 'void', reversed_by = :actor, reversed_at = NOW(), reversal_reason = :reason WHERE id = :id AND status = 'pending'");
            $update->execute([':actor' => $user['id'], ':reason' => $reason !== '' ? $reason : 'Pending payment cancelled by administrator.', ':id' => $paymentId]);
            $event = 'voided';
            $after = $before;
            $after['status'] = 'void';
            $after['reason'] = $reason;
        }
        payment_event($pdo, $paymentId, $user['id'], $event, $before, $after);
        $pdo->commit();
        bamstay_json(['success' => true, 'status' => $after['status']]);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('BAMStay payment update failed: ' . $exception->getMessage());
        bamstay_json(['error' => 'The payment status could not be updated.'], 500);
    }
}

if ($action === 'edit-pending') {
    $paymentId = is_string($input['paymentId'] ?? null) ? $input['paymentId'] : '';
    $amountCents = bamstay_parse_amount($input['amountReceived'] ?? null);
    $method = is_string($input['paymentMethod'] ?? null) ? $input['paymentMethod'] : '';
    $reference = trim(is_string($input['referenceNumber'] ?? null) ? $input['referenceNumber'] : '');
    $paymentDate = trim(is_string($input['paymentDate'] ?? null) ? $input['paymentDate'] : '');
    $notes = trim(is_string($input['notes'] ?? null) ? $input['notes'] : '');
    if (!preg_match('/^[a-f0-9-]{36}$/iD', $paymentId) || $amountCents === null || $amountCents < 1
        || !in_array($method, ['Cash', 'GCash', 'Maya', 'Bank Transfer', 'Other'], true)
        || !bamstay_valid_date($paymentDate) || $paymentDate > $today
        || (in_array($method, ['GCash', 'Maya', 'Bank Transfer'], true) && $reference === '')
        || mb_strlen($reference) > 100 || mb_strlen($notes) > 1000) {
        bamstay_json(['error' => 'Check the edited amount, payment method, reference, and date.'], 422);
    }
    try {
        $pdo->beginTransaction();
        $row = payment_record($pdo, $paymentId, null, true);
        if ($row === false || $row['status'] !== 'pending') {
            $pdo->rollBack();
            bamstay_json(['error' => 'Only existing pending payments can be edited.'], 409);
        }
        $obligation = $pdo->prepare('SELECT amount_due, late_fee FROM rental_obligations WHERE id = :id FOR UPDATE');
        $obligation->execute([':id' => (int) $row['obligation_id']]);
        $amountDue = $obligation->fetch();
        $totals = $pdo->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount_received ELSE 0 END), 0) AS confirmed,
                COALESCE(SUM(CASE WHEN status = 'pending' AND id <> :payment_id THEN amount_received ELSE 0 END), 0) AS other_pending
             FROM payments WHERE obligation_id = :obligation_id"
        );
        $totals->execute([':payment_id' => $paymentId, ':obligation_id' => (int) $row['obligation_id']]);
        $applied = $totals->fetch();
        $ceiling = (bamstay_parse_amount((string) $amountDue['amount_due']) ?? 0)
            + (bamstay_parse_amount((string) $amountDue['late_fee']) ?? 0)
            - (bamstay_parse_amount((string) $applied['confirmed']) ?? 0)
            - (bamstay_parse_amount((string) $applied['other_pending']) ?? 0);
        if ($amountCents > max(0, $ceiling)) {
            $pdo->rollBack();
            bamstay_json(['error' => 'The edited payment exceeds the remaining obligation balance.'], 422);
        }
        $before = payment_snapshot($row);
        $update = $pdo->prepare(
            "UPDATE payments SET amount_received = :amount, payment_method = :method, reference_number = :reference,
                payment_date = :payment_date, notes = :notes WHERE id = :id AND status = 'pending'"
        );
        $update->execute([
            ':amount' => bamstay_money($amountCents),
            ':method' => $method,
            ':reference' => $reference !== '' ? $reference : null,
            ':payment_date' => $paymentDate,
            ':notes' => $notes,
            ':id' => $paymentId,
        ]);
        $after = [
            'amountReceived' => bamstay_money($amountCents),
            'paymentMethod' => $method,
            'referenceNumber' => $reference !== '' ? $reference : null,
            'paymentDate' => $paymentDate,
            'status' => 'pending',
        ];
        payment_event($pdo, $paymentId, $user['id'], 'edited', $before, $after);
        $pdo->commit();
        bamstay_json(['success' => true]);
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception->getCode() === '23000') {
            bamstay_json(['error' => 'That reference number is already on another payment.'], 409);
        }
        error_log('BAMStay pending payment edit failed: ' . $exception->getMessage());
        bamstay_json(['error' => 'The pending payment could not be edited.'], 500);
    }
}

bamstay_json(['error' => 'Unknown payment action.'], 400);
