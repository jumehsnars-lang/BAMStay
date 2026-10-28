<?php
$navLinks = [
    ['Home', 'top'],
    ['About', 'about'],
    ['Rooms', 'rooms'],
    ['Gallery', 'gallery'],
    ['Location', 'location'],
    ['Testimonials', 'testimonials'],
    ['Inquire', 'inquire'],
];
?>
<header class="sticky top-0 z-40 border-b border-border/60 bg-background/90 backdrop-blur">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3" aria-label="Main">
        <a href="#top" aria-label="BAMStay home" class="site-logo">
            <span class="grid h-9 w-9 place-items-center rounded-full bg-primary text-primary-foreground"><?php icon('home', 'h-4 w-4'); ?></span>
            <span class="leading-none">
                <span class="block font-serif text-2xl font-semibold text-primary">BAMStay</span>
                <span class="block text-[0.55rem] uppercase tracking-[0.25em] text-muted-foreground">Ladies' Dormitory</span>
            </span>
        </a>
        <ul class="hidden items-center gap-7 lg:flex">
            <?php foreach ($navLinks as [$label, $id]): ?>
                <li><a href="#<?= e($id) ?>" class="text-xs font-medium uppercase tracking-[0.16em] text-foreground/80 hover:text-primary"><?= e($label) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <div class="hidden items-center gap-3 lg:flex">
            <a href="<?= e($business['tenant_login_url']) ?>" class="btn-outline">Tenant Portal</a>
            <a href="#inquire" class="btn-primary">Inquire Now</a>
        </div>
        <button class="site-theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark mode" title="Switch to dark mode" aria-pressed="false">
            <svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3.5"></circle><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"></path></svg>
            <svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.2 15.1A8.5 8.5 0 0 1 8.9 3.8 8.5 8.5 0 1 0 20.2 15.1Z"></path></svg>
            <span data-theme-label>Dark mode</span>
        </button>
        <button id="mobile-menu-toggle" class="rounded-full p-2 text-primary lg:hidden" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu">
            <?php icon('menu'); ?>
        </button>
    </nav>
    <div id="mobile-menu" class="hidden border-t border-border bg-background px-5 pb-5 lg:hidden">
        <ul class="flex flex-col py-2">
            <?php foreach ($navLinks as [$label, $id]): ?>
                <li><a href="#<?= e($id) ?>" class="block py-3 text-sm uppercase tracking-[0.16em]"><?= e($label) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <div class="flex flex-col gap-2">
            <a href="<?= e($business['tenant_login_url']) ?>" class="btn-outline">Tenant Portal</a>
            <a href="#inquire" class="btn-primary">Inquire Now</a>
        </div>
    </div>
</header>
