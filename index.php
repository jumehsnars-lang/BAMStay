<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

require_once __DIR__ . '/data/site.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$featuredRoom = $rooms[0] ?? null;
foreach ($rooms as $candidate) {
    if ($featuredRoom === null || occupancy($candidate)['available'] > occupancy($featuredRoom)['available']) {
        $featuredRoom = $candidate;
    }
}

$form = ['name' => '', 'phone' => '', 'email' => '', 'message' => ''];
$errors = [];
$submissionStatus = '';
$submissionMessage = '';
$selectedRoomId = $featuredRoom['id'] ?? '';
$selectedBedId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($form) as $field) {
        $value = $_POST[$field] ?? '';
        $form[$field] = is_string($value) ? trim($value) : '';
    }
    $postedRoomId = $_POST['room_id'] ?? '';
    $postedBedId = $_POST['bedspace_id'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';
    $selectedRoomId = is_string($postedRoomId) ? $postedRoomId : '';
    $selectedBedId = is_string($postedBedId) ? $postedBedId : '';

    if (!is_string($postedToken) || !hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $submissionStatus = 'error';
        $submissionMessage = 'Your session expired. Refresh the page and submit your inquiry again.';
    } else {
        if (mb_strlen($form['name']) < 2 || mb_strlen($form['name']) > 100) {
            $errors['name'] = 'Please enter your full name (2 to 100 characters).';
        }
        if (!preg_match('/^(?:\+?63|0)9[0-9]{9}$/D', $form['phone'])) {
            $errors['phone'] = 'Enter a valid mobile number, e.g. 09171234567.';
        }
        if ($form['email'] !== '' && (mb_strlen($form['email']) > 254 || filter_var($form['email'], FILTER_VALIDATE_EMAIL) === false)) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (mb_strlen($form['message']) > 1000) {
            $errors['message'] = 'Message is too long (maximum 1000 characters).';
        }

        $selectedRoom = null;
        foreach ($rooms as $room) {
            if ($room['id'] === $selectedRoomId) {
                $selectedRoom = $room;
                break;
            }
        }
        if ($selectedRoom === null) {
            $errors['room_id'] = 'Please choose a valid room.';
        } elseif ($selectedBedId !== '') {
            $bedIsAvailable = false;
            foreach ($selectedRoom['bedspaces'] as $bed) {
                if ($bed['id'] === $selectedBedId && $bed['status'] === 'available') {
                    $bedIsAvailable = true;
                    break;
                }
            }
            if (!$bedIsAvailable) {
                $errors['bedspace_id'] = 'That bedspace is no longer available. Please select an available bed.';
                $selectedBedId = '';
            }
        }

        if ($errors === []) {
            try {
                /** @var array{host: string, port: int, database: string, username: string, password: string, charset: string} $db */
                $db = require __DIR__ . '/config.php';
                $pdo = new PDO(
                    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
                    $db['username'],
                    $db['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
                $statement = $pdo->prepare(
                    'INSERT INTO inquiries (name, phone, email, room_id, bedspace_id, message) VALUES (:name, :phone, :email, :room_id, :bedspace_id, :message)'
                );
                $statement->execute([
                    ':name' => $form['name'],
                    ':phone' => $form['phone'],
                    ':email' => $form['email'],
                    ':room_id' => $selectedRoomId,
                    ':bedspace_id' => $selectedBedId !== '' ? $selectedBedId : null,
                    ':message' => $form['message'],
                ]);

                if ($statement->rowCount() !== 1) {
                    throw new RuntimeException('Inquiry insert did not save a row.');
                }

                $submissionStatus = 'success';
                $submissionMessage = 'Your inquiry was sent. BAMStay will get back to you soon.';
                $form = ['name' => '', 'phone' => '', 'email' => '', 'message' => ''];
                $selectedBedId = '';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (PDOException | RuntimeException $exception) {
                error_log('BAMStay inquiry submission failed: ' . $exception->getMessage());
                $submissionStatus = 'error';
                $submissionMessage = 'We could not save your inquiry. Check that MySQL is running, the database is configured, and the SQL setup script has been imported, then try again.';
            }
        }
    }
}

$selectedRoom = null;
foreach ($rooms as $room) {
    if ($room['id'] === $selectedRoomId) {
        $selectedRoom = $room;
        break;
    }
}
if ($selectedRoom === null) {
    $selectedRoom = $featuredRoom;
    $selectedRoomId = $selectedRoom['id'] ?? '';
}
$availableBeds = $selectedRoom === null
    ? []
    : array_values(array_filter($selectedRoom['bedspaces'], static fn (array $bed): bool => $bed['status'] === 'available'));
$previewBed = null;
foreach ($availableBeds as $bed) {
    if ($bed['id'] === $selectedBedId) {
        $previewBed = $bed;
        break;
    }
}
if ($previewBed === null && $availableBeds !== []) {
    $previewBed = $availableBeds[0];
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $previewBed !== null) {
    $selectedBedId = $previewBed['id'];
}

$roomClientData = [];
foreach ($rooms as $room) {
    $clientRoom = [
        'id' => $room['id'],
        'name' => $room['name'],
        'image' => $room['image'],
        'description' => $room['description'],
        'rate' => rate_label($room['monthly_rate']),
        'monthlyRate' => $room['monthly_rate'],
        'beds' => [],
        'counts' => occupancy($room),
    ];
    foreach ($room['bedspaces'] as $bed) {
        $clientRoom['beds'][] = [
            'id' => $bed['id'],
            'label' => $bed['label'],
            'status' => $bed['status'],
            'image' => $bed['image'] ?? null,
        ];
    }
    $roomClientData[] = $clientRoom;
}

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/navbar.php';
?>
<main>
    <?php require __DIR__ . '/partials/sections.php'; ?>
</main>
<?php
require __DIR__ . '/partials/footer.php';
require __DIR__ . '/partials/chatbot.php';
require __DIR__ . '/partials/modals.php';
?>
<script type="application/json" id="room-data"><?= json_encode($roomClientData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script src="assets/js/site.js" defer></script>
<script src="assets/js/theme-toggle.js" defer></script>
<script src="assets/js/chatbot.js" defer></script>
</body>
</html>
