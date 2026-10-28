<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/security.php';
bamstay_start_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !hash_equals(bamstay_csrf_token(), $token)) {
    http_response_code(419);
    exit('Your session expired. Refresh the page and try again.');
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $parameters = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $parameters['path'], $parameters['domain'], $parameters['secure'], $parameters['httponly']);
}
session_destroy();
header('Location: login.php');
exit;
