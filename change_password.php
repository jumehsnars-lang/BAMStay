<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/security.php';
bamstay_start_session();
$user = bamstay_require_portal_user();
$error = '';
if ($user['role'] !== 'tenant') {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['password_confirmation'] ?? '';
    if (!is_string($token) || !hash_equals(bamstay_csrf_token(), $token)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!is_string($password) || strlen($password) < 12 || strlen($password) > 200) {
        $error = 'Choose a password with at least 12 characters.';
    } elseif (!is_string($confirmation) || !hash_equals($password, $confirmation)) {
        $error = 'The password confirmation does not match.';
    } else {
        try {
            $pdo = bamstay_database();
            $statement = $pdo->prepare('UPDATE tenants SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id AND status = \'active\'');
            $statement->execute([':password_hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $user['tenant_id']]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('Tenant password was not updated.');
            }
            $_SESSION['portal_user']['must_change_password'] = false;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: dashboard.php');
            exit;
        } catch (Throwable $exception) {
            error_log('BAMStay first sign-in password update failed: ' . $exception->getMessage());
            $error = 'Your password could not be changed. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change password — BAMStay</title>
    <script>
        try {
            const savedTheme = localStorage.getItem("bamstay-theme");
            document.documentElement.dataset.theme = savedTheme === "dark" || savedTheme === "light"
                ? savedTheme
                : (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
        } catch {
            document.documentElement.dataset.theme = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        }
    </script>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <main class="auth-page">
        <button class="theme-toggle auth-theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark mode" title="Switch to dark mode" aria-pressed="false">
            <span class="theme-toggle-track" aria-hidden="true">
                <svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3.5"></circle><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"></path></svg>
                <svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" fill="none"><path d="M20.2 15.1A8.5 8.5 0 0 1 8.9 3.8 8.5 8.5 0 1 0 20.2 15.1Z"></path></svg>
            </span>
            <span data-theme-label>Dark mode</span>
        </button>
        <form class="auth-card" method="post">
            <a class="brand auth-brand" href="index.php"><span class="brand-mark">B</span><span><strong>BAMStay</strong><small>RESIDENT PORTAL</small></span></a>
            <h1>Set a new password</h1>
            <p class="page-subtitle">For your security, change the temporary password before continuing.</p>
            <?php if ($error !== ''): ?><p class="auth-error" role="alert"><?= e($error) ?></p><?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= e(bamstay_csrf_token()) ?>">
            <label class="form-field">New password<input type="password" name="password" autocomplete="new-password" minlength="12" required></label>
            <label class="form-field">Confirm new password<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required></label>
            <button type="submit" class="btn btn-primary auth-submit">Change password</button>
        </form>
    </main>
    <script src="assets/js/theme-toggle.js" defer></script>
</body>
</html>
