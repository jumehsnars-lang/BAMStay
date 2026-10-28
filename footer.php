<footer class="bg-primary-deep text-primary-foreground">
    <div class="mx-auto grid max-w-7xl gap-8 px-5 py-14 md:grid-cols-3">
        <div>
            <a href="#top" class="site-logo site-logo-light" aria-label="BAMStay home">
                <span class="grid h-9 w-9 place-items-center rounded-full bg-cream text-primary-deep"><?php icon('home', 'h-4 w-4'); ?></span>
                <span class="leading-none">
                    <span class="block font-serif text-2xl font-semibold text-primary-foreground">BAMStay</span>
                    <span class="block text-[0.55rem] uppercase tracking-[0.25em] text-primary-foreground/70">Ladies' Dormitory</span>
                </span>
            </a>
            <p class="mt-4 max-w-xs text-sm text-primary-foreground/70"><?= e($business['tagline']) ?> A safe, welcoming ladies' dormitory and bedspace accommodation.</p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-primary-foreground/60">Quick Links</p>
            <ul class="mt-4 grid grid-cols-2 gap-2 text-sm">
                <?php foreach ([['Rooms', 'rooms'], ['About', 'about'], ['Location', 'location'], ['Inquire', 'inquire']] as [$label, $id]): ?>
                    <li><a href="#<?= e($id) ?>" class="hover:underline"><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="text-sm text-primary-foreground/80">
            <p class="text-xs uppercase tracking-[0.2em] text-primary-foreground/60">Contact</p>
            <p class="mt-4">Phone coming soon</p>
            <p>Email coming soon</p>
        </div>
    </div>
    <p class="border-t border-primary-foreground/15 py-5 text-center text-xs text-primary-foreground/60">© <?= date('Y') ?> BAMStay. All rights reserved.</p>
</footer>
