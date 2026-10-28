<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/data/site.php';

bamstay_start_session();
$user = bamstay_require_portal_user(true);
if ($user['role'] !== 'tenant' || $user['tenant_id'] === null) {
    bamstay_json(['error' => 'This information is available to tenant accounts only.'], 403);
}

try {
    $pdo = bamstay_database();
    $tenantQuery = $pdo->prepare(
        "SELECT id, full_name, email, phone, emergency_contact, room_id, bedspace_id, move_in_date, monthly_rent
         FROM tenants WHERE id = :id AND status = 'active'"
    );
    $tenantQuery->execute([':id' => $user['tenant_id']]);
    $tenant = $tenantQuery->fetch();
    if ($tenant === false) {
        bamstay_json(['error' => 'The tenant account is inactive or no longer exists.'], 404);
    }

    $room = null;
    foreach ($rooms as $candidate) {
        if ($candidate['id'] === $tenant['room_id']) {
            $room = $candidate;
            break;
        }
    }
    $roomImage = $room['image'] ?? 'room1.jpg';
    $seedBeds = $room['bedspaces'] ?? [];
    $roomQuery = $pdo->prepare(
        'SELECT id, name, description, features FROM property_rooms WHERE id = :id'
    );
    $roomQuery->execute([':id' => $tenant['room_id']]);
    $roomRecord = $roomQuery->fetch();
    if ($roomRecord === false) {
        bamstay_json(['error' => 'The assigned room is not present in the room catalogue. Contact the property administrator.'], 409);
    }
    $room = array_merge($roomRecord, ['image' => $roomImage]);
    $features = json_decode((string) $roomRecord['features'], true);
    $room['features'] = is_array($features) ? array_values(array_filter($features, 'is_string')) : [];
    $beds = [];
    $bedspacesQuery = $pdo->prepare(
        "SELECT b.id, b.label, b.status, active.id AS tenant_id, active.full_name AS tenant_name
         FROM property_bedspaces b
         LEFT JOIN tenants active
           ON active.room_id = b.room_id AND active.bedspace_id = b.id AND active.status = 'active'
         WHERE b.room_id = :room_id ORDER BY b.label"
    );
    $bedspacesQuery->execute([':room_id' => $tenant['room_id']]);
    foreach ($bedspacesQuery->fetchAll() as $bed) {
        $isYou = $bed['tenant_id'] !== null && (int) $bed['tenant_id'] === $user['tenant_id'];
        $status = $bed['tenant_id'] !== null
            ? 'Occupied'
            : ($bed['status'] === 'maintenance' ? 'Maintenance' : 'Available');
        $seedBed = null;
        foreach ($seedBeds as $candidateBed) {
            if ($candidateBed['id'] === $bed['id']) {
                $seedBed = $candidateBed;
                break;
            }
        }
        $beds[] = [
            'id' => $bed['id'],
            'label' => $bed['label'],
            'status' => $status,
            'tenantName' => $bed['tenant_name'],
            'isYou' => $isYou,
            'image' => bed_image($room, array_merge($seedBed ?? [], ['id' => (string) $bed['id']])),
        ];
    }

    $latestPaymentQuery = $pdo->prepare(
        "SELECT p.status, p.payment_date, p.amount_received, p.receipt_number, o.billing_month, o.due_date,
                o.amount_due + o.late_fee AS obligation_amount,
                COALESCE(applied.confirmed, 0) AS confirmed_amount
         FROM rental_obligations o
         JOIN payments p ON p.obligation_id = o.id
         LEFT JOIN (
            SELECT obligation_id, SUM(amount_received) AS confirmed
            FROM payments WHERE status = 'confirmed' GROUP BY obligation_id
         ) applied ON applied.obligation_id = o.id
         WHERE o.tenant_id = :tenant_id
         ORDER BY o.billing_month DESC, p.created_at DESC LIMIT 1"
    );
    $latestPaymentQuery->execute([':tenant_id' => $user['tenant_id']]);
    $latest = $latestPaymentQuery->fetch();
    $latestPayment = $latest === false ? null : [
        'paymentStatus' => $latest['status'] ?? null,
        'paymentDate' => $latest['payment_date'] ?? null,
        'billingMonth' => $latest['billing_month'],
        'dueDate' => $latest['due_date'],
        'obligationAmount' => $latest['obligation_amount'],
        'confirmedAmount' => $latest['confirmed_amount'],
        'receiptNumber' => $latest['receipt_number'] ?? null,
    ];

    bamstay_json([
        'tenant' => [
            'id' => (int) $tenant['id'],
            'fullName' => $tenant['full_name'],
            'email' => $tenant['email'],
            'phone' => $tenant['phone'],
            'emergencyContact' => $tenant['emergency_contact'],
            'roomId' => $tenant['room_id'],
            'roomName' => $roomRecord['name'],
            'bedspaceId' => $tenant['bedspace_id'],
            'bedspaceLabel' => array_values(array_filter($beds, static fn (array $bed): bool => $bed['id'] === $tenant['bedspace_id']))[0]['label'] ?? $tenant['bedspace_id'],
            'moveInDate' => $tenant['move_in_date'],
            'monthlyRent' => $tenant['monthly_rent'],
        ],
        'room' => [
            'name' => $roomRecord['name'],
            'image' => room_image_url($room),
            'description' => $room['description'],
            'features' => $room['features'],
            'beds' => $beds,
        ],
        'latestPayment' => $latestPayment,
    ]);
} catch (PDOException $exception) {
    error_log('BAMStay tenant profile lookup failed: ' . $exception->getMessage());
    bamstay_json(['error' => 'Your resident information could not be loaded.'], 500);
}
