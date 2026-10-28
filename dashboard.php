<?php
declare(strict_types=1);

require_once __DIR__ . '/data/site.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

bamstay_start_session();
$user = bamstay_require_portal_user();
if ($user['role'] === 'tenant' && !empty($_SESSION['portal_user']['must_change_password'])) {
    header('Location: change_password.php');
    exit;
}

$roomCards = [];
foreach ($rooms as $room) {
    $roomCards[] = [
        'id' => $room['id'],
        'room_number' => $room['name'],
        'name' => $room['name'],
        'image' => room_image_url($room),
        'description' => $room['description'],
        'features' => $room['features'],
        'rate' => rate_label($room['monthly_rate']),
        'monthlyRate' => $room['monthly_rate'],
        'status' => 'active',
        'beds' => array_map(static fn (array $bed): array => $bed + ['tenantName' => null], $room['bedspaces']),
        'counts' => occupancy($room),
    ];
}
$initialRole = $user['role'];
$initial = mb_strtoupper(mb_substr($user['email'], 0, 1));
$dashboardCssVersion = filemtime(__DIR__ . '/assets/css/dashboard.css');
$dashboardJsVersion = filemtime(__DIR__ . '/assets/js/dashboard.js');
$gcashQrPath = __DIR__ . '/assets/images/gcash-qr.png';
$gcashQrUrl = is_file($gcashQrPath)
    ? image_url('gcash-qr.png') . '?v=' . filemtime($gcashQrPath)
    : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BAMStay tenant and admin dashboard.">
    <title>BAMStay Portal — Dashboard</title>
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        try {
            const savedTheme = localStorage.getItem("bamstay-theme");
            document.documentElement.dataset.theme = savedTheme === "dark" || savedTheme === "light"
                ? savedTheme
                : (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
            document.documentElement.dataset.sidebarCollapsed = localStorage.getItem("bamstay-sidebar-collapsed") === "true" ? "true" : "false";
        } catch {
            document.documentElement.dataset.theme = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            document.documentElement.dataset.sidebarCollapsed = "false";
        }
    </script>
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= e((string) $dashboardCssVersion) ?>">
</head>
<body>
    <div class="portal-shell" id="portal" data-initial-role="<?= e($initialRole) ?>" data-csrf-token="<?= e(bamstay_csrf_token()) ?>" data-payment-api="api/payments.php" data-tenant-api="api/tenants.php" data-tenant-portal-api="api/tenant_portal.php" data-rooms-api="api/rooms.php" data-room-photo-api="api/room_photo.php" data-gcash-qr-url="<?= e($gcashQrUrl) ?>">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand-row">
                <a class="brand" href="index.php" aria-label="BAMStay home">
                    <span class="brand-mark">B</span>
                    <span class="brand-copy"><strong>BAMStay</strong><small>RESIDENT PORTAL</small></span>
                </a>
                <button class="sidebar-collapse-toggle" id="sidebar-collapse-toggle" type="button" aria-label="Collapse sidebar" aria-expanded="true" title="Collapse sidebar">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m14.5 5-7 7 7 7M20 5v14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <div class="workspace-label">WORKSPACE</div>
            <nav class="side-nav" id="side-nav" aria-label="Dashboard navigation"></nav>
            <div class="sidebar-bottom">
                <a class="side-link back-link" href="index.php" data-label="Back to website" aria-label="Back to website"><span class="nav-icon">↗</span><span class="side-link-label">Back to website</span></a>
                <div class="user-mini" title="<?= e($user['email']) ?>"><span class="avatar" id="mini-avatar"><?= e($initial) ?></span><span class="user-mini-copy"><strong id="mini-name"><?= e($user['email']) ?></strong><small id="mini-role"><?= e(ucfirst($initialRole)) ?></small></span></div>
            </div>
        </aside>
        <div class="workspace">
            <header class="topbar">
                <button class="icon-button mobile-menu" type="button" aria-label="Open menu" id="menu-toggle">☰</button>
                <div class="breadcrumbs"><span>Workspace</span><span class="crumb-sep">/</span><strong id="crumb-label">Overview</strong></div>
                <div class="topbar-actions">
                    <span class="role-switch"><span>Signed in as</span><strong><?= e(ucfirst($initialRole)) ?></strong></span>
                    <button class="theme-toggle" id="theme-toggle" type="button" aria-label="Switch to dark mode" aria-pressed="false" title="Switch to dark mode">
                        <span class="theme-toggle-track" aria-hidden="true">
                            <svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3.5"></circle><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"></path></svg>
                            <svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" fill="none"><path d="M20.2 15.1A8.5 8.5 0 0 1 8.9 3.8 8.5 8.5 0 1 0 20.2 15.1Z"></path></svg>
                        </span>
                        <span class="theme-toggle-label">Dark mode</span>
                    </button>
                    <button class="icon-button notification-button" type="button" aria-label="Notifications" title="Notifications"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4"></path></svg><i></i></button>
                    <span class="avatar top-avatar" id="top-avatar"><?= e($initial) ?></span>
                    <form method="post" action="logout.php">
                        <input type="hidden" name="csrf_token" value="<?= e(bamstay_csrf_token()) ?>">
                        <button class="btn btn-light btn-sm" type="submit">Sign out</button>
                    </form>
                </div>
            </header>
            <main class="page-content" id="page-content"></main>
            <footer class="portal-footer"><span>© <?= date('Y') ?> BAMStay · Made for a place to call home.</span><span>Tenant &amp; Admin Portal</span></footer>
        </div>
    </div>
    <div class="modal-backdrop" id="modal-backdrop" hidden>
        <section class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <button type="button" class="modal-close" aria-label="Close dialog">×</button>
            <div id="modal-content"></div>
        </section>
    </div>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
    <script type="application/json" id="room-data"><?= json_encode($roomCards, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
    <script src="assets/js/dashboard.js?v=<?= e((string) $dashboardJsVersion) ?>" defer></script>
</body>
</html>
