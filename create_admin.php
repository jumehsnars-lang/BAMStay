<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/includes/security.php';
$email = strtolower(trim($argv[1] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php tools\\create_admin.php admin@example.com\n");
    exit(2);
}
$password = readline('New admin password (minimum 12 characters): ');
if (!is_string($password) || strlen($password) < 12) {
    fwrite(STDERR, "Password must contain at least 12 characters.\n");
    exit(2);
}

try {
    $pdo = bamstay_database();
    $statement = $pdo->prepare('INSERT INTO admin_users (email, password_hash) VALUES (:email, :password_hash)');
    $statement->execute([':email' => $email, ':password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Administrator account created for {$email}.\n");
} catch (PDOException $exception) {
    fwrite(STDERR, "Could not create administrator: " . $exception->getMessage() . "\n");
    exit(1);
}
