<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/data/site.php';

bamstay_start_session();
bamstay_require_admin(true);
$pdo = bamstay_database();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query("SELECT id, full_name, email, phone, emergency_contact, room_id, bedspace_id, move_in_date, monthly_rent, status FROM tenants ORDER BY full_name")->fetchAll();
    $roomLabels = [];
    $roomRows = $pdo->query(
        "SELECT r.id, r.name, r.monthly_rate, r.status AS room_status,
                b.id AS bed_id, b.label AS bed_label, b.status AS bed_status,
                active.id AS active_tenant_id
         FROM property_rooms r
         LEFT JOIN property_bedspaces b ON b.room_id = r.id
         LEFT JOIN tenants active
           ON active.room_id = r.id AND active.bedspace_id = b.id AND active.status = 'active'
         ORDER BY r.room_number, b.label"
    )->fetchAll();
    $availableRoomsById = [];
    foreach ($roomRows as $roomRow) {
        if ($roomRow['bed_id'] === null) {
            continue;
        }
        $key = $roomRow['id'] . ':' . $roomRow['bed_id'];
        $roomLabels[$key] = [$roomRow['name'], $roomRow['bed_label']];
        if ($roomRow['room_status'] === 'active' && $roomRow['active_tenant_id'] === null && $roomRow['bed_status'] === 'available') {
            if (!isset($availableRoomsById[$roomRow['id']])) {
                $availableRoomsById[$roomRow['id']] = [
                    'id' => $roomRow['id'],
                    'name' => $roomRow['name'],
                    'monthlyRent' => $roomRow['monthly_rate'],
                    'beds' => [],
                ];
            }
            $availableRoomsById[$roomRow['id']]['beds'][] = ['id' => $roomRow['bed_id'], 'label' => $roomRow['bed_label']];
        }
    }
    foreach ($rows as &$row) {
        [$row['room_name'], $row['bed_label']] = $roomLabels[$row['room_id'] . ':' . $row['bedspace_id']] ?? [$row['room_id'], $row['bedspace_id']];
    }
    unset($row);
    bamstay_json(['tenants' => $rows, 'rooms' => array_values($availableRoomsById), 'csrfToken' => bamstay_csrf_token()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    bamstay_json(['error' => 'Method not allowed.'], 405);
}
bamstay_check_csrf();
$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    bamstay_json(['error' => 'Invalid request data.'], 400);
}

$name = trim(is_string($input['fullName'] ?? null) ? $input['fullName'] : '');
$email = strtolower(trim(is_string($input['email'] ?? null) ? $input['email'] : ''));
$phone = trim(is_string($input['phone'] ?? null) ? $input['phone'] : '');
$emergency = trim(is_string($input['emergencyContact'] ?? null) ? $input['emergencyContact'] : '');
$roomId = trim(is_string($input['roomId'] ?? null) ? $input['roomId'] : '');
$bedId = trim(is_string($input['bedspaceId'] ?? null) ? $input['bedspaceId'] : '');
$moveInDate = trim(is_string($input['moveInDate'] ?? null) ? $input['moveInDate'] : '');
$rentCents = bamstay_parse_amount($input['monthlyRent'] ?? null);

if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
    bamstay_json(['error' => 'Enter the tenant’s full name (2 to 150 characters).', 'field' => 'fullName'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
    bamstay_json(['error' => 'Enter a valid login email address.', 'field' => 'email'], 422);
}
if (!preg_match('/^(?:\+?63|0)9[0-9]{9}$/D', $phone)) {
    bamstay_json(['error' => 'Enter a valid Philippine mobile number.', 'field' => 'phone'], 422);
}
if (mb_strlen($emergency) > 150) {
    bamstay_json(['error' => 'Emergency contact must be 150 characters or fewer.', 'field' => 'emergencyContact'], 422);
}
if ($rentCents === null || $rentCents < 1) {
    bamstay_json(['error' => 'Enter a valid monthly rent greater than zero.', 'field' => 'monthlyRent'], 422);
}
$date = DateTimeImmutable::createFromFormat('!Y-m-d', $moveInDate);
if ($date === false || $date->format('Y-m-d') !== $moveInDate || $date > new DateTimeImmutable('today')) {
    bamstay_json(['error' => 'Enter a valid move-in date that is not in the future.', 'field' => 'moveInDate'], 422);
}

$temporaryPassword = rtrim(strtr(base64_encode(random_bytes(15)), '+/', 'AZ'), '=');
try {
    $pdo->beginTransaction();
    $bed = $pdo->prepare(
        "SELECT b.id FROM property_bedspaces b
         JOIN property_rooms r ON r.id = b.room_id
         WHERE b.id = :bedspace_id AND b.room_id = :room_id
           AND b.status = 'available' AND r.status = 'active'
         FOR UPDATE"
    );
    $bed->execute([':bedspace_id' => $bedId, ':room_id' => $roomId]);
    if ($bed->fetchColumn() === false) {
        $pdo->rollBack();
        bamstay_json(['error' => 'Select a currently listed available bedspace in an active room.', 'field' => 'bedspaceId'], 422);
    }
    $duplicate = $pdo->prepare('SELECT email FROM admin_users WHERE email = :email UNION SELECT email FROM tenants WHERE email = :tenant_email LIMIT 1');
    $duplicate->execute([':email' => $email, ':tenant_email' => $email]);
    if ($duplicate->fetchColumn() !== false) {
        $pdo->rollBack();
        bamstay_json(['error' => 'An account already uses this email address.', 'field' => 'email'], 409);
    }
    $assigned = $pdo->prepare("SELECT id FROM tenants WHERE room_id = :room_id AND bedspace_id = :bedspace_id AND status = 'active' FOR UPDATE");
    $assigned->execute([':room_id' => $roomId, ':bedspace_id' => $bedId]);
    if ($assigned->fetchColumn() !== false) {
        $pdo->rollBack();
        bamstay_json(['error' => 'That bedspace has already been assigned to an active tenant.', 'field' => 'bedspaceId'], 409);
    }
    $insert = $pdo->prepare(
        'INSERT INTO tenants (full_name, email, password_hash, phone, emergency_contact, room_id, bedspace_id, move_in_date, monthly_rent) VALUES (:full_name, :email, :password_hash, :phone, :emergency_contact, :room_id, :bedspace_id, :move_in_date, :monthly_rent)'
    );
    $insert->execute([
        ':full_name' => $name,
        ':email' => $email,
        ':password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
        ':phone' => $phone,
        ':emergency_contact' => $emergency,
        ':room_id' => $roomId,
        ':bedspace_id' => $bedId,
        ':move_in_date' => $moveInDate,
        ':monthly_rent' => bamstay_money($rentCents),
    ]);
    $tenantId = (int) $pdo->lastInsertId();
    $pdo->commit();
    bamstay_json([
        'tenant' => ['id' => $tenantId, 'fullName' => $name, 'email' => $email],
        'temporaryPassword' => $temporaryPassword,
    ], 201);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('BAMStay tenant creation failed: ' . $exception->getMessage());
    bamstay_json(['error' => 'The tenant account could not be created. Check for an existing email or bed assignment.'], 500);
}
