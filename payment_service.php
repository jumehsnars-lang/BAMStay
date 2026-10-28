<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

function bamstay_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
}

function bamstay_valid_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function bamstay_month_start(string $month): ?string
{
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $month)) {
        return null;
    }
    return $month . '-01';
}

function bamstay_ensure_obligations(PDO $pdo): void
{
    $tenants = $pdo->query("SELECT id, move_in_date, monthly_rent FROM tenants WHERE status = 'active' ORDER BY id")->fetchAll();
    $insert = $pdo->prepare(
        'INSERT IGNORE INTO rental_obligations (tenant_id, billing_month, amount_due, due_date) VALUES (:tenant_id, :billing_month, :amount_due, :due_date)'
    );
    $currentMonth = new DateTimeImmutable('first day of this month');
    foreach ($tenants as $tenant) {
        $month = new DateTimeImmutable((string) $tenant['move_in_date']);
        $month = $month->modify('first day of this month');
        while ($month <= $currentMonth) {
            $billingMonth = $month->format('Y-m-d');
            $insert->execute([
                ':tenant_id' => (int) $tenant['id'],
                ':billing_month' => $billingMonth,
                ':amount_due' => $tenant['monthly_rent'],
                ':due_date' => $billingMonth,
            ]);
            $month = $month->modify('+1 month');
        }
    }
}

/** @return array{confirmed: int, pending: int, remaining: int, status: string} */
function bamstay_obligation_totals(PDO $pdo, int $obligationId, string $due, string $today): array
{
    $statement = $pdo->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount_received ELSE 0 END), 0) AS confirmed,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN amount_received ELSE 0 END), 0) AS pending
         FROM payments WHERE obligation_id = :obligation_id"
    );
    $statement->execute([':obligation_id' => $obligationId]);
    $amounts = $statement->fetch();
    $obligation = $pdo->prepare('SELECT amount_due, late_fee FROM rental_obligations WHERE id = :id');
    $obligation->execute([':id' => $obligationId]);
    $dueAmounts = $obligation->fetch();
    if ($amounts === false || $dueAmounts === false) {
        throw new RuntimeException('Rental obligation was not found.');
    }
    $totalDue = (bamstay_parse_amount((string) $dueAmounts['amount_due']) ?? 0)
        + (bamstay_parse_amount((string) $dueAmounts['late_fee']) ?? 0);
    $confirmed = bamstay_parse_amount((string) $amounts['confirmed']) ?? 0;
    $pending = bamstay_parse_amount((string) $amounts['pending']) ?? 0;
    $remaining = max(0, $totalDue - $confirmed);
    if ($remaining === 0) {
        $status = 'Paid';
    } elseif ($due < $today) {
        $status = 'Overdue';
    } elseif ($confirmed > 0) {
        $status = 'Partially Paid';
    } elseif ($pending > 0) {
        $status = 'Pending';
    } else {
        $status = 'Due';
    }
    return ['confirmed' => $confirmed, 'pending' => $pending, 'remaining' => $remaining, 'status' => $status];
}

/** @return array{totalCollected: string, outstandingBalance: string, overdueAmount: string, overdueTenantCount: int, pendingCount: int, unpaidObligationCount: int} */
function bamstay_payment_summary(PDO $pdo): array
{
    $currentMonth = (new DateTimeImmutable('first day of this month'))->format('Y-m-d');
    $nextMonth = (new DateTimeImmutable('first day of next month'))->format('Y-m-d');
    $collected = $pdo->prepare(
        "SELECT COALESCE(SUM(amount_received), 0) FROM payments
         WHERE status = 'confirmed' AND payment_date >= :month_start AND payment_date < :next_month"
    );
    $collected->execute([':month_start' => $currentMonth, ':next_month' => $nextMonth]);
    $obligations = $pdo->query(
        "SELECT o.id, o.due_date, o.amount_due, o.late_fee,
            COALESCE(SUM(CASE WHEN p.status = 'confirmed' THEN p.amount_received ELSE 0 END), 0) AS confirmed,
            COUNT(DISTINCT CASE WHEN p.status = 'pending' THEN p.id END) AS pending_count,
            COUNT(DISTINCT CASE WHEN p.status = 'confirmed' THEN p.id END) AS confirmed_count,
            t.id AS tenant_id
         FROM rental_obligations o
         JOIN tenants t ON t.id = o.tenant_id
         LEFT JOIN payments p ON p.obligation_id = o.id
         GROUP BY o.id, o.due_date, o.amount_due, o.late_fee, t.id"
    )->fetchAll();

    $outstanding = 0;
    $overdue = 0;
    $overdueTenants = [];
    $pendingCount = 0;
    $unpaidObligationCount = 0;
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    foreach ($obligations as $row) {
        $due = (bamstay_parse_amount((string) $row['amount_due']) ?? 0)
            + (bamstay_parse_amount((string) $row['late_fee']) ?? 0);
        $paid = bamstay_parse_amount((string) $row['confirmed']) ?? 0;
        $remaining = max(0, $due - $paid);
        $outstanding += $remaining;
        $pendingCount += (int) $row['pending_count'];
        if ($remaining > 0) {
            $unpaidObligationCount++;
        }
        if ($remaining > 0 && $row['due_date'] < $today) {
            $overdue += $remaining;
            $overdueTenants[(int) $row['tenant_id']] = true;
        }
    }
    $pendingSubmissionCount = (int) $pdo->query("SELECT COUNT(*) FROM payment_submissions WHERE status = 'pending'")->fetchColumn();
    return [
        'totalCollected' => (string) $collected->fetchColumn(),
        'outstandingBalance' => bamstay_money($outstanding),
        'overdueAmount' => bamstay_money($overdue),
        'overdueTenantCount' => count($overdueTenants),
        'pendingCount' => $pendingCount + $pendingSubmissionCount,
        'unpaidObligationCount' => $unpaidObligationCount,
    ];
}
