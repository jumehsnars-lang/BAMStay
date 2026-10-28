<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/data/site.php';

bamstay_start_session();
bamstay_require_admin(true);
$pdo = bamstay_database();

function room_management_list(PDO $pdo, array $seedRooms): array
{
    $seeds = [];
    foreach ($seedRooms as $seed) {
        $seeds[$seed['id']] = $seed;
    }

    $rooms = $pdo->query(
        'SELECT id, room_number, name, description, features, monthly_rate, status
         FROM property_rooms ORDER BY room_number, name'
    )->fetchAll();
    $bedsQuery = $pdo->query(
        "SELECT b.id, b.room_id, b.label, b.status, active.full_name AS tenant_name,
                (assigned.bedspace_id IS NOT NULL) AS has_tenant_record
         FROM property_bedspaces b
         LEFT JOIN tenants active
           ON active.room_id = b.room_id AND active.bedspace_id = b.id AND active.status = 'active'
         LEFT JOIN (
            SELECT DISTINCT room_id, bedspace_id FROM tenants
         ) assigned ON assigned.room_id = b.room_id AND assigned.bedspace_id = b.id
         ORDER BY b.room_id, b.label"
    );
    $bedsByRoom = [];
    foreach ($bedsQuery->fetchAll() as $bed) {
        $status = $bed['tenant_name'] !== null ? 'occupied' : (string) $bed['status'];
        $seedRoom = $seeds[$bed['room_id']] ?? ['id' => $bed['room_id'], 'image' => 'room1.jpg', 'bedspaces' => []];
        $seedBed = null;
        foreach ($seedRoom['bedspaces'] as $candidateBed) {
            if ($candidateBed['id'] === $bed['id']) {
                $seedBed = $candidateBed;
                break;
            }
        }
        $bedsByRoom[$bed['room_id']][] = [
            'id' => (string) $bed['id'],
            'label' => (string) $bed['label'],
            'status' => $status,
            'tenantName' => $bed['tenant_name'],
            'hasTenantRecord' => (bool) $bed['has_tenant_record'],
            'image' => bed_image($seedRoom, array_merge($seedBed ?? [], ['id' => (string) $bed['id']])),
        ];
    }

    $result = [];
    foreach ($rooms as $room) {
        $beds = $bedsByRoom[$room['id']] ?? [];
        $total = count($beds);
        $occupied = count(array_filter($beds, static fn (array $bed): bool => $bed['status'] === 'occupied'));
        $available = count(array_filter($beds, static fn (array $bed): bool => $bed['status'] === 'available'));
        $seed = $seeds[$room['id']] ?? ['image' => 'room1.jpg'];
        $room['image'] = room_image_url(['id' => $room['id'], 'image' => $seed['image']]);
        $features = json_decode((string) $room['features'], true);
        $room['features'] = is_array($features) ? array_values(array_filter($features, 'is_string')) : [];
        $room['monthlyRate'] = (string) $room['monthly_rate'];
        $room['status'] = (string) $room['status'];
        $room['beds'] = $beds;
        $room['counts'] = ['total' => $total, 'occupied' => $occupied, 'available' => $available];
        unset($room['monthly_rate']);
        $result[] = $room;
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        bamstay_json(['rooms' => room_management_list($pdo, $rooms)]);
    } catch (PDOException $exception) {
        error_log('BAMStay room catalogue lookup failed: ' . $exception->getMessage());
        bamstay_json(['error' => 'Room data is unavailable. Import the room management migration and try again.'], 503);
    }
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

$action = $input['action'] ?? '';
try {
    if ($action === 'save-room') {
        $id = is_string($input['id'] ?? null) ? trim($input['id']) : '';
        $number = trim(is_string($input['roomNumber'] ?? null) ? $input['roomNumber'] : '');
        $name = trim(is_string($input['name'] ?? null) ? $input['name'] : '');
        $description = trim(is_string($input['description'] ?? null) ? $input['description'] : '');
        $features = $input['features'] ?? null;
        $rate = bamstay_parse_amount($input['monthlyRate'] ?? null);
        $status = $input['status'] ?? '';
        if ($id !== '' && !preg_match('/^room-[a-f0-9]{12}$/D', $id) && !in_array($id, array_column($rooms, 'id'), true)) {
            bamstay_json(['error' => 'That room could not be found.'], 404);
        }
        if ($number === '' || mb_strlen($number) > 60) {
            bamstay_json(['error' => 'Enter a room number or identifier (up to 60 characters).', 'field' => 'roomNumber'], 422);
        }
        if ($name === '' || mb_strlen($name) > 100) {
            bamstay_json(['error' => 'Enter a room name (up to 100 characters).', 'field' => 'name'], 422);
        }
        if (mb_strlen($description) > 2000) {
            bamstay_json(['error' => 'Room description must be 2,000 characters or fewer.', 'field' => 'description'], 422);
        }
        if (
            !is_array($features)
            || count($features) > 20
            || array_filter($features, static fn (mixed $feature): bool => !is_string($feature) || trim($feature) === '' || mb_strlen(trim($feature)) > 60) !== []
        ) {
            bamstay_json(['error' => 'Enter up to 20 room features, each no longer than 60 characters.', 'field' => 'features'], 422);
        }
        if ($rate === null || $rate < 1) {
            bamstay_json(['error' => 'Enter a valid monthly rent greater than zero.', 'field' => 'monthlyRate'], 422);
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            bamstay_json(['error' => 'Choose Active or Inactive for the room status.', 'field' => 'status'], 422);
        }

        $features = array_values(array_unique(array_map(static fn (string $feature): string => trim($feature), $features)));
        if ($id === '') {
            $id = 'room-' . bin2hex(random_bytes(6));
            $insert = $pdo->prepare(
                'INSERT INTO property_rooms (id, room_number, name, description, features, monthly_rate, status)
                 VALUES (:id, :room_number, :name, :description, :features, :monthly_rate, :status)'
            );
            $insert->execute([
                ':id' => $id,
                ':room_number' => $number,
                ':name' => $name,
                ':description' => $description,
                ':features' => json_encode($features, JSON_THROW_ON_ERROR),
                ':monthly_rate' => bamstay_money($rate),
                ':status' => $status,
            ]);
        } else {
            $update = $pdo->prepare(
                'UPDATE property_rooms SET room_number = :room_number, name = :name, description = :description,
                 features = :features, monthly_rate = :monthly_rate, status = :status WHERE id = :id'
            );
            $update->execute([
                ':id' => $id,
                ':room_number' => $number,
                ':name' => $name,
                ':description' => $description,
                ':features' => json_encode($features, JSON_THROW_ON_ERROR),
                ':monthly_rate' => bamstay_money($rate),
                ':status' => $status,
            ]);
            if ($update->rowCount() === 0) {
                $exists = $pdo->prepare('SELECT id FROM property_rooms WHERE id = :id');
                $exists->execute([':id' => $id]);
                if ($exists->fetchColumn() === false) {
                    bamstay_json(['error' => 'That room could not be found.'], 404);
                }
            }
        }
        bamstay_json(['rooms' => room_management_list($pdo, $rooms), 'savedRoomId' => $id]);
    }

    if ($action === 'toggle-room') {
        $id = is_string($input['roomId'] ?? null) ? $input['roomId'] : '';
        $status = $input['status'] ?? '';
        if (!in_array($status, ['active', 'inactive'], true)) {
            bamstay_json(['error' => 'Choose Active or Inactive for the room status.'], 422);
        }
        $update = $pdo->prepare('UPDATE property_rooms SET status = :status WHERE id = :id');
        $update->execute([':status' => $status, ':id' => $id]);
        if ($update->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT id FROM property_rooms WHERE id = :id');
            $exists->execute([':id' => $id]);
            if ($exists->fetchColumn() === false) {
                bamstay_json(['error' => 'That room could not be found.'], 404);
            }
        }
        bamstay_json(['rooms' => room_management_list($pdo, $rooms)]);
    }

    if ($action === 'save-bedspace') {
        $id = is_string($input['id'] ?? null) ? trim($input['id']) : '';
        $roomId = is_string($input['roomId'] ?? null) ? trim($input['roomId']) : '';
        $label = trim(is_string($input['label'] ?? null) ? $input['label'] : '');
        $status = $input['status'] ?? '';
        if ($label === '' || mb_strlen($label) > 60) {
            bamstay_json(['error' => 'Enter a bedspace label (up to 60 characters).', 'field' => 'label'], 422);
        }
        if (!in_array($status, ['available', 'maintenance'], true)) {
            bamstay_json(['error' => 'Bedspace status must be Available or Maintenance.', 'field' => 'status'], 422);
        }
        $pdo->beginTransaction();
        $roomQuery = $pdo->prepare('SELECT id FROM property_rooms WHERE id = :id FOR UPDATE');
        $roomQuery->execute([':id' => $roomId]);
        if ($roomQuery->fetchColumn() === false) {
            bamstay_json(['error' => 'That room could not be found.'], 404);
        }
        if ($id === '') {
            $bedCountQuery = $pdo->prepare('SELECT COUNT(*) FROM property_bedspaces WHERE room_id = :room_id');
            $bedCountQuery->execute([':room_id' => $roomId]);
            if ((int) $bedCountQuery->fetchColumn() >= 50) {
                bamstay_json(['error' => 'A room can have no more than 50 bedspaces.'], 422);
            }
            $id = $roomId . '-b' . bin2hex(random_bytes(4));
            $insert = $pdo->prepare('INSERT INTO property_bedspaces (id, room_id, label, status) VALUES (:id, :room_id, :label, :status)');
            $insert->execute([':id' => $id, ':room_id' => $roomId, ':label' => $label, ':status' => $status]);
        } else {
            $tenantQuery = $pdo->prepare("SELECT id FROM tenants WHERE room_id = :room_id AND bedspace_id = :bedspace_id AND status = 'active' FOR UPDATE");
            $tenantQuery->execute([':room_id' => $roomId, ':bedspace_id' => $id]);
            if ($tenantQuery->fetchColumn() !== false && $status !== 'available') {
                bamstay_json(['error' => 'An occupied bedspace cannot be marked for maintenance. Reassign or deactivate the tenant first.'], 409);
            }
            $update = $pdo->prepare('UPDATE property_bedspaces SET label = :label, status = :status WHERE id = :id AND room_id = :room_id');
            $update->execute([':label' => $label, ':status' => $status, ':id' => $id, ':room_id' => $roomId]);
            if ($update->rowCount() === 0) {
                $exists = $pdo->prepare('SELECT id FROM property_bedspaces WHERE id = :id AND room_id = :room_id');
                $exists->execute([':id' => $id, ':room_id' => $roomId]);
                if ($exists->fetchColumn() === false) {
                    bamstay_json(['error' => 'That bedspace could not be found.'], 404);
                }
            }
        }
        $pdo->commit();
        bamstay_json(['rooms' => room_management_list($pdo, $rooms), 'savedBedspaceId' => $id]);
    }

    if ($action === 'delete-bedspace') {
        $id = is_string($input['bedspaceId'] ?? null) ? trim($input['bedspaceId']) : '';
        $pdo->beginTransaction();
        $bedQuery = $pdo->prepare('SELECT id, room_id FROM property_bedspaces WHERE id = :id FOR UPDATE');
        $bedQuery->execute([':id' => $id]);
        $bed = $bedQuery->fetch();
        if ($bed === false) {
            bamstay_json(['error' => 'That bedspace could not be found.'], 404);
        }
        $tenantQuery = $pdo->prepare('SELECT id FROM tenants WHERE room_id = :room_id AND bedspace_id = :bedspace_id LIMIT 1');
        $tenantQuery->execute([':room_id' => $bed['room_id'], ':bedspace_id' => $id]);
        if ($tenantQuery->fetchColumn() !== false) {
            bamstay_json(['error' => 'This bedspace is linked to a tenant record and cannot be deleted. Mark it for maintenance instead.'], 409);
        }
        $delete = $pdo->prepare('DELETE FROM property_bedspaces WHERE id = :id');
        $delete->execute([':id' => $id]);
        $pdo->commit();
        bamstay_json(['rooms' => room_management_list($pdo, $rooms)]);
    }

    bamstay_json(['error' => 'Choose a supported room or bedspace action.'], 422);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($exception->getCode() === '23000') {
        bamstay_json(['error' => 'That room number or bedspace label is already in use.'], 409);
    }
    error_log('BAMStay room management update failed: ' . $exception->getMessage());
    bamstay_json(['error' => 'The room or bedspace change could not be saved. Check the room management migration and try again.'], 500);
}
