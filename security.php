<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function bamstay_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
    ]);
}

function bamstay_database(): PDO
{
    /** @var array{host: string, port: int, database: string, username: string, password: string, charset: string} $db */
    $db = require dirname(__DIR__) . '/config.php';
    return new PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
        $db['username'],
        $db['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function bamstay_csrf_token(): string
{
    bamstay_start_session();
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** @return array{id: int, role: string, tenant_id: ?int, email: string}|null */
function bamstay_current_user(): ?array
{
    bamstay_start_session();
    $user = $_SESSION['portal_user'] ?? null;
    if (!is_array($user) || !isset($user['id'], $user['role'], $user['email'])) {
        return null;
    }

    return [
        'id' => (int) $user['id'],
        'role' => (string) $user['role'],
        'tenant_id' => isset($user['tenant_id']) ? (int) $user['tenant_id'] : null,
        'email' => (string) $user['email'],
    ];
}

function bamstay_require_admin(bool $json = false): ?array
{
    $user = bamstay_current_user();
    if ($user !== null && $user['role'] === 'admin') {
        return $user;
    }
    if ($json) {
        bamstay_json(['error' => 'Administrator sign-in is required.'], 401);
    }
    header('Location: login.php');
    exit;
}

function bamstay_require_portal_user(bool $json = false): ?array
{
    $user = bamstay_current_user();
    if ($user !== null && in_array($user['role'], ['admin', 'tenant'], true)) {
        return $user;
    }
    if ($json) {
        bamstay_json(['error' => 'Please sign in to continue.'], 401);
    }
    header('Location: login.php');
    exit;
}

function bamstay_check_csrf(): void
{
    bamstay_start_session();
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($provided) || !hash_equals(bamstay_csrf_token(), $provided)) {
        bamstay_json(['error' => 'Your session expired. Refresh the page and try again.'], 419);
    }
}

function bamstay_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    exit;
}

function bamstay_parse_amount(mixed $value): ?int
{
    if (!is_string($value) && !is_int($value)) {
        return null;
    }
    $normalized = (string) $value;
    if (!preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?$/D', $normalized)) {
        return null;
    }
    [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '0');
    return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
}

function bamstay_money(int $cents): string
{
    return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
}
