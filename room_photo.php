<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/security.php';

bamstay_start_session();
$user = bamstay_require_admin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    bamstay_json(['error' => 'Use POST to upload a room or bedspace photo.'], 405);
}

bamstay_check_csrf();

$targetType = $_POST['targetType'] ?? 'room';
$roomId = $_POST['roomId'] ?? '';
$bedspaceId = $_POST['bedspaceId'] ?? '';
$upload = $_FILES['photo'] ?? null;
if (!in_array($targetType, ['room', 'bedspace'], true)) {
    bamstay_json(['error' => 'Choose a room or bedspace photo destination.'], 422);
}
try {
    $pdo = bamstay_database();
    if ($targetType === 'room') {
        if (!is_string($roomId) || !preg_match('/^room-[a-z0-9-]+$/D', $roomId)) {
            bamstay_json(['error' => 'Choose a valid room before uploading a photo.'], 422);
        }
        $targetId = $roomId;
        $roomExists = $pdo->prepare('SELECT id FROM property_rooms WHERE id = :id');
        $roomExists->execute([':id' => $targetId]);
        if ($roomExists->fetchColumn() === false) {
            bamstay_json(['error' => 'That room could not be found.'], 404);
        }
    } else {
        if (!is_string($bedspaceId) || !preg_match('/^room-[a-z0-9-]+$/D', $bedspaceId)) {
            bamstay_json(['error' => 'Choose a valid bedspace before uploading a photo.'], 422);
        }
        $targetId = $bedspaceId;
        $bedspaceExists = $pdo->prepare('SELECT id FROM property_bedspaces WHERE id = :id');
        $bedspaceExists->execute([':id' => $targetId]);
        if ($bedspaceExists->fetchColumn() === false) {
            bamstay_json(['error' => 'That bedspace could not be found.'], 404);
        }
    }
} catch (PDOException $exception) {
    error_log('BAMStay room or bedspace photo lookup failed: ' . $exception->getMessage());
    bamstay_json(['error' => 'Room data is unavailable. Import the room management migration and try again.'], 503);
}
if (!is_array($upload) || !isset($upload['error'], $upload['tmp_name'], $upload['size'])) {
    bamstay_json(['error' => 'Choose a photo to upload.'], 422);
}
if ((int) $upload['error'] !== UPLOAD_ERR_OK) {
    $message = match ((int) $upload['error']) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The photo is too large. Choose an image under 5 MB.',
        UPLOAD_ERR_NO_FILE => 'Choose a photo to upload.',
        default => 'The photo could not be uploaded. Please try again.',
    };
    bamstay_json(['error' => $message], 422);
}
if ((int) $upload['size'] < 1 || (int) $upload['size'] > 5 * 1024 * 1024 || !is_uploaded_file((string) $upload['tmp_name'])) {
    bamstay_json(['error' => 'Choose an image under 5 MB.'], 422);
}

$imageInfo = @getimagesize((string) $upload['tmp_name']);
$fileInfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $fileInfo->file((string) $upload['tmp_name']);
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
if (
    $imageInfo === false
    || !isset($allowedTypes[$mimeType])
    || $imageInfo['mime'] !== $mimeType
    || $imageInfo[0] < 300
    || $imageInfo[1] < 200
    || $imageInfo[0] > 8000
    || $imageInfo[1] > 8000
    || $imageInfo[0] * $imageInfo[1] > 25000000
) {
    bamstay_json(['error' => 'Use a valid JPG, PNG, or WebP photo between 300 × 200 and 8,000 × 8,000 pixels.'], 422);
}

$directory = dirname(__DIR__) . '/assets/images/room-uploads';
if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    bamstay_json(['error' => 'The room photo folder could not be created. Check folder permissions.'], 500);
}

$extension = $allowedTypes[$mimeType];
$filename = $targetId . '.' . $extension;
$destination = $directory . '/' . $filename;
if (!move_uploaded_file((string) $upload['tmp_name'], $destination)) {
    bamstay_json(['error' => 'The photo could not be saved. Check folder permissions and try again.'], 500);
}

foreach (array_diff(['jpg', 'png', 'webp'], [$extension]) as $oldExtension) {
    $oldPhoto = $directory . '/' . $targetId . '.' . $oldExtension;
    if (is_file($oldPhoto) && !unlink($oldPhoto)) {
        error_log('BAMStay could not remove replaced room image: ' . $oldPhoto);
    }
}

bamstay_json([
    'targetType' => $targetType,
    'targetId' => $targetId,
    'image' => 'assets/images/room-uploads/' . $filename . '?v=' . filemtime($destination),
]);
