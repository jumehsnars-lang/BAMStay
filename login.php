<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/security.php';
bamstay_start_session();

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailInput = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';

    if (!is_string($token) || !hash_equals(bamstay_csrf_token(), $token)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) || $password === '') {
        $error = 'Enter a valid email address and password.';
    } else {
        try {
            $pdo = bamstay_database();
            $admin = $pdo->prepare('SELECT id, email, password_hash FROM admin_users WHERE email = :email AND is_active = 1 LIMIT 1');
            $admin->execute([':email' => $email]);
            $account = $admin->fetch();
            $role = 'admin';
            $tenantId = null;
            if ($account === false) {
                $tenant = $pdo->prepare("SELECT id, email, password_hash, must_change_password FROM tenants WHERE email = :email AND status = 'active' LIMIT 1");
                $tenant->execute([':email' => $email]);
                $account = $tenant->fetch();
                $role = 'tenant';
                $tenantId = $account === false ? null : (int) $account['id'];
            }

            if ($account !== false && password_verify($password, $account['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['portal_user'] = [
                    'id' => (int) $account['id'],
                    'role' => $role,
                    'tenant_id' => $tenantId,
                    'email' => (string) $account['email'],
                    'must_change_password' => $role === 'tenant' && (bool) ($account['must_change_password'] ?? false),
                ];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                if ($role === 'tenant' && !empty($_SESSION['portal_user']['must_change_password'])) {
                    header('Location: change_password.php');
                    exit;
                }
                header('Location: dashboard.php');
                exit;
            }
            $error = 'The email or password is incorrect.';
        } catch (PDOException $exception) {
            error_log('BAMStay sign-in failed: ' . $exception->getMessage());
            $error = 'Sign-in is temporarily unavailable. Check that the database migration has been applied.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — BAMStay</title>
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
            <h1>Welcome back</h1>
            <p class="page-subtitle">Sign in to your BAMStay account.</p>
            <?php if ($error !== ''): ?><p class="auth-error" role="alert"><?= e($error) ?></p><?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= e(bamstay_csrf_token()) ?>">
            <label class="form-field">Email address<input type="email" name="email" autocomplete="username" required value="<?= e($email) ?>"></label>
            <label class="form-field">Password<input type="password" name="password" autocomplete="current-password" required></label>
            <button type="submit" class="btn btn-primary auth-submit">Sign in</button>
            <a class="auth-back" href="index.php">Back to BAMStay website</a>
        </form>
    </main>
    <script src="assets/js/theme-toggle.js" defer></script>
</body>
</html>
