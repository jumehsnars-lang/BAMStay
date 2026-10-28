<?php
$totalTenants = array_sum(array_map(static fn (array $room): int => occupancy($room)['occupied'], $rooms));
?>
<section id="top" class="relative overflow-hidden">
    <div class="mx-auto grid max-w-7xl items-center gap-10 px-5 pb-20 pt-10 md:grid-cols-[0.9fr_1.1fr] md:pt-16">
        <div>
            <p class="eyebrow">Safe Stays · Comfortable Living</p>
            <h1 class="mt-4 text-5xl leading-[1.02] text-foreground md:text-6xl lg:text-7xl">More Than a Room, <em class="text-primary">It's Your Home.</em></h1>
            <p class="mt-6 max-w-md text-muted-foreground">Discover a comfortable and welcoming place to stay at BAMStay, where everyday living meets convenience, comfort, and peace of mind.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#rooms" class="btn-primary">Explore Rooms <?php icon('arrow-right', 'h-4 w-4'); ?></a>
                <a href="<?= e($business['tenant_login_url']) ?>" class="btn-outline">Tenant Portal</a>
            </div>
        </div>
        <div class="relative">
            <div class="absolute -inset-4 -z-10 blob bg-secondary" aria-hidden="true"></div>
            <img src="<?= e(image_url('bamstay-exterior.png')) ?>" width="1600" height="912" alt="BAMStay dormitory exterior" class="aspect-[4/3] w-full rounded-[2.5rem_8rem_2.5rem_2.5rem] object-cover shadow-soft">
        </div>
    </div>
    <div class="mx-auto -mt-10 max-w-6xl px-5">
        <ul class="grid grid-cols-2 gap-4 rounded-3xl border border-border bg-card p-6 shadow-soft md:grid-cols-4">
            <?php foreach ($highlights as $highlight): ?>
                <li class="flex items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-secondary text-primary"><?php icon($highlight['icon'], 'h-5 w-5'); ?></span>
                    <span>
                        <span class="block font-serif text-lg font-semibold"><?= e($highlight['title']) ?></span>
                        <span class="block text-xs text-muted-foreground"><?= e($highlight['description']) ?></span>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section id="about" class="bg-cream py-24">
    <div class="mx-auto max-w-7xl px-5">
        <div class="grid items-center gap-12 md:grid-cols-2">
            <div class="relative order-2 md:order-1">
                <div class="absolute -left-6 -top-6 h-40 w-40 blob bg-primary/15" aria-hidden="true"></div>
                <img src="<?= e(image_url('bamstay-hallway.png')) ?>" alt="Hallway inside BAMStay" loading="lazy" class="relative aspect-[5/4] w-full rounded-[45%_55%_40%_60%/55%_45%_55%_45%] object-cover shadow-soft">
            </div>
            <div class="order-1 md:order-2">
                <?php section_heading('About BAMStay', 'A Trusted Home', 'for Ladies', null, false); ?>
                <p class="mt-5 text-muted-foreground">BAMStay provides a welcoming living environment for female students and boarders. Our goal is to offer comfortable accommodation, convenient facilities, and a supportive place where residents can focus on their studies and everyday activities.</p>
                <dl class="mt-8 grid grid-cols-3 gap-3">
                    <?php foreach ([['Total Rooms', (string) count($rooms)], ['Current Tenants', (string) $totalTenants], ['Years of Operation', '—']] as [$label, $value]): ?>
                        <div class="rounded-2xl bg-card p-4 text-center shadow-soft">
                            <dd class="font-serif text-4xl font-semibold text-primary"><?= e($value) ?></dd>
                            <dt class="mt-1 text-[0.65rem] uppercase tracking-widest text-muted-foreground"><?= e($label) ?></dt>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>

        <div class="about-facilities-section">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-4xl text-foreground md:text-5xl">Everything You Need, <em class="text-primary">Close to Home</em></h2>
            </div>
            <ul class="about-facilities-grid mt-7">
                <?php foreach ($facilities as $facility): ?>
                    <li class="about-facility-card rounded-2xl bg-card p-4 shadow-soft transition-transform duration-300 hover:-translate-y-1">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-primary text-primary-foreground"><?php icon($facility['icon'], 'h-4 w-4'); ?></span>
                        <h3 class="mt-3 text-base font-semibold leading-snug"><?= e($facility['title']) ?></h3>
                        <p class="mt-1 text-xs leading-relaxed text-muted-foreground"><?= e($facility['description']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>

<section id="rooms" class="mx-auto max-w-7xl px-5 py-24">
    <?php section_heading('Our Rooms', 'Find Your', 'Space', 'Explore our rooms and discover the bedspace that suits your needs.'); ?>
    <?php if ($rooms === []): ?>
        <p class="mt-12 rounded-3xl border border-dashed border-border p-10 text-center text-muted-foreground">No rooms are listed right now. Please send us an inquiry.</p>
    <?php else: ?>
        <div class="relative mt-12">
            <?php if (count($rooms) > 1): ?>
                <button type="button" data-room-move="-1" aria-label="Show previous rooms" class="absolute left-2 top-1/2 z-10 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full border border-border bg-card/95 text-primary shadow-soft transition hover:bg-card focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><?php icon('chevron-left', 'h-6 w-6'); ?></button>
                <button type="button" data-room-move="1" aria-label="Show next rooms" class="absolute right-2 top-1/2 z-10 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full border border-border bg-card/95 text-primary shadow-soft transition hover:bg-card focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><?php icon('chevron-right', 'h-6 w-6'); ?></button>
            <?php endif; ?>
            <div id="room-carousel" class="flex snap-x snap-mandatory gap-6 overflow-x-auto scroll-smooth px-1 pb-3">
                <?php foreach ($rooms as $room): $counts = occupancy($room); ?>
                    <article class="group flex w-[88%] min-w-[88%] shrink-0 snap-start flex-col overflow-hidden rounded-3xl border border-border bg-card shadow-soft sm:w-[calc((100%-1.5rem)/2)] sm:min-w-[calc((100%-1.5rem)/2)] lg:w-[calc((100%-3rem)/3)] lg:min-w-[calc((100%-3rem)/3)]">
                        <div class="relative overflow-hidden">
                            <img src="<?= e(image_url($room['image'])) ?>" alt="<?= e($room['name']) ?>" loading="lazy" width="1024" height="768" class="aspect-[4/3] w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <div class="absolute left-4 top-4">
                                <?php if ($counts['available'] > 0): ?>
                                    <span class="rounded-full bg-success px-3 py-1 text-[0.65rem] font-medium uppercase tracking-widest text-primary-foreground"><?= e($counts['available']) ?> available</span>
                                <?php else: ?>
                                    <span class="rounded-full bg-primary-deep px-3 py-1 text-[0.65rem] font-medium uppercase tracking-widest text-primary-foreground">Fully occupied</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col gap-4 p-5">
                            <div>
                                <h3 class="text-3xl"><?= e($room['name']) ?></h3>
                                <p class="mt-1 text-sm text-muted-foreground"><?= e($room['description']) ?></p>
                                <p class="mt-3 font-serif text-2xl text-primary"><?= e(rate_label($room['monthly_rate'])) ?><span class="font-sans text-xs text-muted-foreground"> / bedspace / month</span></p>
                            </div>
                            <dl class="grid grid-cols-3 gap-2 text-center">
                                <?php foreach (['Total' => $counts['total'], 'Occupied' => $counts['occupied'], 'Available' => $counts['available']] as $label => $number): ?>
                                    <div class="rounded-xl bg-muted py-2"><dt class="text-[0.6rem] uppercase tracking-widest text-muted-foreground"><?= e($label) ?></dt><dd class="font-serif text-2xl font-semibold"><?= e($number) ?></dd></div>
                                <?php endforeach; ?>
                            </dl>
                            <button type="button" data-open-room="<?= e($room['id']) ?>" class="btn-outline mt-auto">View Room Details <?php icon('arrow-right', 'h-4 w-4'); ?></button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php if ($featuredRoom !== null): ?>
    <section class="mx-auto max-w-7xl px-5">
        <div class="grid overflow-hidden rounded-[2rem] bg-primary text-primary-foreground md:grid-cols-2">
            <div class="flex flex-col items-start justify-center gap-6 p-7 sm:p-9 md:p-10">
                <div>
                    <p class="mb-3 text-xs uppercase tracking-[0.22em] text-primary-foreground/70">Your home away from home</p>
                    <h2 class="max-w-md font-serif text-3xl leading-tight md:text-4xl">Take a little peek inside BAMStay, your cozy home away from home!</h2>
                </div>
                <a href="#rooms" class="btn-light">Explore Our Rooms <?php icon('arrow-right', 'h-4 w-4'); ?></a>
            </div>
            <div class="relative h-full min-h-64 overflow-hidden bg-primary-deep">
                <div class="flex h-full min-h-64 items-center justify-center bg-gradient-to-br from-primary-deep via-primary to-primary/80 p-6">
                    <div class="rounded-2xl border border-white/20 bg-black/20 px-5 py-4 text-center backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-[0.22em] text-primary-foreground/70">Dorm Tour</p>
                        <p class="mt-2 text-xl font-medium">Video coming soon</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<section id="gallery" class="mx-auto max-w-7xl px-5 py-24">
    <?php section_heading('Our Gallery', 'Take a Look', 'Around BAMStay', 'A glimpse of the spaces that make BAMStay feel like home.'); ?>
    <?php if ($gallery !== []): ?>
        <div class="relative mx-auto mt-12 max-w-7xl px-7 sm:px-10">
            <div class="overflow-hidden py-8">
                <div id="gallery-track" class="flex w-max will-change-transform">
                    <?php foreach ([false, true] as $isClone): ?>
                        <div class="gallery-group flex shrink-0 gap-3 pr-3"<?= $isClone ? ' aria-hidden="true"' : '' ?>>
                            <?php foreach ($gallery as $index => $image): ?>
                                <?php if ($isClone): ?>
                                    <div class="gallery-card group relative aspect-square w-[calc(85vw-3.75rem)] shrink-0 overflow-hidden rounded-3xl bg-muted shadow-soft transition-[transform,box-shadow] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] hover:z-20 hover:scale-110 hover:shadow-[0_28px_60px_-16px_hsl(var(--primary)/0.5)] motion-reduce:transition-none sm:w-[calc((min(100vw-6rem,80rem)-0.75rem)/2)] lg:w-[calc((min(100vw-8rem,80rem)-2.25rem)/4)]">
                                        <img src="<?= e(image_url($image['src'])) ?>" alt="" loading="lazy" class="h-full w-full object-cover transition-transform duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-110">
                                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent px-5 pb-4 pt-14 text-lg font-semibold text-white"><?= e($image['label']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <button type="button" data-gallery-photo="<?= e($index) ?>" aria-label="View <?= e($image['label']) ?>: <?= e($image['alt']) ?>" class="gallery-card group relative aspect-square w-[calc(85vw-3.75rem)] shrink-0 overflow-hidden rounded-3xl bg-muted text-left shadow-soft transition-[transform,box-shadow,outline-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] hover:z-20 hover:scale-110 hover:shadow-[0_28px_60px_-16px_hsl(var(--primary)/0.5)] hover:outline hover:outline-2 hover:outline-primary/50 focus-visible:z-20 focus-visible:scale-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring motion-reduce:transition-none sm:w-[calc((min(100vw-6rem,80rem)-0.75rem)/2)] lg:w-[calc((min(100vw-8rem,80rem)-2.25rem)/4)]">
                                        <img src="<?= e(image_url($image['src'])) ?>" alt="<?= e($image['alt']) ?>" loading="lazy" class="h-full w-full object-cover transition-transform duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-110">
                                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent px-5 pb-4 pt-14 text-lg font-semibold text-white transition-[padding,background-color] duration-500 ease-out group-hover:pb-6 group-hover:pt-20 group-hover:from-black/95"><?= e($image['label']) ?></span>
                                    </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="pointer-events-none absolute inset-x-0 top-1/2 z-20 flex -translate-y-1/2 justify-between">
                <button type="button" data-gallery-move="-1" aria-label="Show previous gallery photo" class="pointer-events-auto grid h-11 w-11 place-items-center rounded-full border border-border bg-card text-primary shadow-soft transition duration-200 hover:scale-110 hover:border-primary hover:bg-primary hover:text-primary-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><?php icon('chevron-left', 'h-6 w-6'); ?></button>
                <button type="button" data-gallery-move="1" aria-label="Show next gallery photo" class="pointer-events-auto grid h-11 w-11 place-items-center rounded-full border border-border bg-card text-primary shadow-soft transition duration-200 hover:scale-110 hover:border-primary hover:bg-primary hover:text-primary-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><?php icon('chevron-right', 'h-6 w-6'); ?></button>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php
$mapQuery = rawurlencode($business['map_query']);
$mapEmbedQuery = rawurlencode($business['map_embed_query']);
?>
<section id="location" class="relative isolate overflow-hidden bg-gradient-to-br from-cream via-background to-secondary/50 py-24">
    <div class="pointer-events-none absolute -right-24 -top-28 -z-10 h-80 w-80 rounded-full bg-primary/10 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-36 -left-24 -z-10 h-96 w-96 rounded-full bg-secondary blur-3xl" aria-hidden="true"></div>
    <div class="mx-auto max-w-7xl px-5">
        <div class="mb-12 max-w-2xl">
            <span class="inline-flex items-center gap-2 rounded-full border border-primary/15 bg-card/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-primary shadow-soft"><?php icon('pin', 'h-3.5 w-3.5'); ?> Nasugbu, Batangas</span>
            <?php section_heading('Find your way to us', 'A Cozy Stay,', 'Close to Everything', null, false); ?>
            <p class="mt-4 max-w-xl text-muted-foreground">Find BAMStay in a convenient spot in Nasugbu, with everyday essentials and local destinations nearby.</p>
        </div>
        <div class="grid items-stretch gap-6 lg:grid-cols-[0.85fr_1.15fr]">
            <div class="flex flex-col gap-5">
                <div class="relative overflow-hidden rounded-[2rem] bg-primary p-7 text-primary-foreground shadow-soft sm:p-8">
                    <div class="absolute -right-8 -top-12 h-44 w-44 rounded-full border border-primary-foreground/15" aria-hidden="true"></div>
                    <div class="absolute -right-1 -top-5 h-32 w-32 rounded-full border border-primary-foreground/10" aria-hidden="true"></div>
                    <div class="relative">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-foreground/70">Our address</p>
                        <p class="mt-4 flex gap-3 text-lg font-medium leading-relaxed sm:text-xl"><?php icon('pin', 'mt-1 h-5 w-5 shrink-0'); ?><span><?= e($business['address']) ?>, <?= e($business['municipality']) ?>, <?= e($business['province']) ?></span></p>
                        <a class="mt-6 inline-flex items-center gap-2 rounded-full bg-card px-5 py-3 text-sm font-semibold text-primary transition hover:-translate-y-0.5 hover:shadow-lg" target="_blank" rel="noreferrer" href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= e($mapQuery) ?>"><?php icon('navigation', 'h-4 w-4'); ?> Get directions</a>
                    </div>
                </div>
                <div class="rounded-[2rem] border border-border/70 bg-card/90 p-6 shadow-soft sm:p-7">
                    <div class="mb-5 flex items-end justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary">Explore nearby</p>
                            <h3 class="mt-1 font-serif text-2xl">Around the neighborhood</h3>
                        </div>
                        <?php icon('pin', 'mb-1 h-5 w-5 text-primary/60'); ?>
                    </div>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        <?php foreach ($business['landmarks'] as $index => $landmark): ?>
                            <li class="flex min-h-14 items-center gap-3 rounded-2xl bg-cream/80 px-4 py-3 text-sm text-foreground/80">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-primary/10 text-xs font-semibold text-primary"><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                                <?= e($landmark) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="relative min-h-[24rem] overflow-hidden rounded-[2rem] border border-border/70 bg-card p-2 shadow-soft sm:min-h-[34rem]">
                <div class="absolute left-6 top-6 z-10 rounded-2xl border border-border bg-card px-4 py-3 shadow-soft">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Come Visit Bistro Bam Dormitories</p>
                </div>
                <iframe title="BAMStay map" loading="lazy" class="h-full min-h-[23rem] w-full rounded-[1.5rem] sm:min-h-[32rem]" src="https://maps.google.com/maps?q=<?= e($mapEmbedQuery) ?>&amp;z=16&amp;output=embed"></iframe>
            </div>
        </div>
    </div>
</section>

<?php if ($testimonials !== []): ?>
    <section id="testimonials" class="mx-auto max-w-7xl px-5 py-24">
        <?php section_heading('Kind Words', 'Stories from', 'Our Residents'); ?>
        <div class="mt-12 grid gap-6 md:grid-cols-3">
            <?php foreach ($testimonials as $testimonial): ?>
                <figure class="rounded-3xl bg-card p-7 shadow-soft">
                    <?php if (!empty($testimonial['placeholder'])): ?>
                        <span class="inline-flex rounded-full bg-secondary px-3 py-1 text-[0.65rem] font-medium uppercase tracking-widest text-primary">Sample placeholder</span>
                    <?php elseif ($testimonial['rating'] !== null): ?>
                        <div class="flex gap-0.5 text-warning" aria-label="<?= e($testimonial['rating']) ?> of 5 stars"><?= str_repeat('★', (int) $testimonial['rating']) ?></div>
                    <?php endif; ?>
                    <blockquote class="mt-4 font-serif text-xl italic">"<?= e($testimonial['text']) ?>"</blockquote>
                    <figcaption class="mt-5 flex items-center gap-3">
                        <?php if (empty($testimonial['placeholder'])): ?>
                            <span class="grid h-10 w-10 place-items-center rounded-full bg-secondary font-semibold text-primary"><?= e(mb_substr($testimonial['name'], 0, 1)) ?></span>
                        <?php endif; ?>
                        <span class="text-sm font-medium"><?= e($testimonial['name']) ?><?php if (!empty($testimonial['placeholder'])): ?><span class="block text-xs text-muted-foreground">Replace with a real, approved resident review</span><?php endif; ?></span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($announcements !== []): ?>
    <section class="mx-auto max-w-5xl px-5 pb-24">
        <?php section_heading('Announcements', 'Latest', 'Notices'); ?>
        <ul class="mt-10 space-y-4">
            <?php foreach ($announcements as $announcement): ?>
                <li class="rounded-2xl border bg-card p-5">
                    <p class="text-xs uppercase tracking-widest text-muted-foreground"><?= e($announcement['type']) ?> · <?= e($announcement['date']) ?></p>
                    <h3 class="text-xl"><?= e($announcement['title']) ?></h3>
                    <p class="text-sm text-muted-foreground"><?= e($announcement['body']) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="mx-auto max-w-7xl px-5 pb-24">
    <div class="grid items-center overflow-hidden rounded-[2rem] bg-primary-deep text-primary-foreground md:grid-cols-[1.2fr_1fr]">
        <div class="p-10 md:p-14">
            <p class="text-xs uppercase tracking-[0.22em] text-primary-foreground/70">Ready to move in?</p>
            <h2 class="mt-3 text-4xl md:text-5xl">Your Next Chapter <em>Starts Here.</em></h2>
            <p class="mt-4 max-w-md text-primary-foreground/80">Looking for a comfortable place to stay? Explore our rooms or send us an inquiry to learn more about available bedspaces.</p>
            <a href="#inquire" class="btn-light mt-7">Send an Inquiry <?php icon('arrow-right', 'h-4 w-4'); ?></a>
        </div>
        <img src="<?= e(image_url('bamstay-exterior.png')) ?>" alt="BAMStay dormitory exterior" loading="lazy" class="h-full min-h-60 w-full object-cover md:rounded-l-[8rem]">
    </div>
</section>

<section id="inquire" class="mx-auto max-w-7xl px-5 py-24">
    <?php section_heading('Inquire', 'Reserve Your', 'Bedspace', "Tell us a little about yourself and we'll get back to you."); ?>
    <div class="mt-12 grid gap-8 lg:grid-cols-2">
        <?php if ($selectedRoom !== null): $roomCounts = occupancy($selectedRoom); ?>
            <aside id="inquiry-preview" class="overflow-hidden rounded-3xl border border-border bg-card shadow-soft" aria-live="polite">
                <div class="relative overflow-hidden bg-muted">
                    <img id="inquiry-preview-image" src="<?= e($previewBed !== null ? bed_image($selectedRoom, $previewBed) : image_url($selectedRoom['image'])) ?>" alt="<?= e($selectedRoom['name'] . ($previewBed !== null ? ' ' . $previewBed['label'] : '')) ?> photo" loading="lazy" class="aspect-[16/10] w-full object-cover transition-opacity duration-300">
                    <span id="inquiry-preview-bed" class="absolute bottom-4 left-4 rounded-full border border-white/30 bg-foreground/75 px-4 py-2 text-sm font-semibold text-white shadow-soft backdrop-blur"><?= $previewBed !== null ? e($previewBed['label']) . ' · Available' : '' ?></span>
                </div>
                <div class="space-y-4 p-7">
                    <div class="flex items-baseline justify-between gap-3">
                        <h3 id="inquiry-preview-title" class="text-3xl"><?= e($selectedRoom['name'] . ($previewBed !== null ? ' · ' . $previewBed['label'] : '')) ?></h3>
                        <p id="inquiry-preview-rate" class="font-serif text-2xl text-primary"><?= e(rate_label($selectedRoom['monthly_rate'])) ?><span class="font-sans text-xs text-muted-foreground"> /mo</span></p>
                    </div>
                    <p id="inquiry-preview-description" class="text-sm text-muted-foreground"><?= e($selectedRoom['description']) ?></p>
                    <p id="inquiry-preview-counts" class="text-sm"><?= e($roomCounts['available']) ?> available · <?= e($roomCounts['occupied']) ?> occupied</p>
                </div>
            </aside>
        <?php endif; ?>
        <form method="post" action="#inquire" class="space-y-5 rounded-3xl bg-cream p-7 md:p-9" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <div>
                <label for="name" class="text-sm font-medium">Full Name *</label>
                <input id="name" name="name" type="text" maxlength="100" autocomplete="name" required class="form-field" value="<?= e($form['name']) ?>" aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>"<?= isset($errors['name']) ? ' aria-describedby="name-error"' : '' ?>>
                <?php if (isset($errors['name'])): ?><p id="name-error" class="mt-1 text-xs text-destructive" role="alert"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="phone" class="text-sm font-medium">Contact Number *</label>
                    <input id="phone" name="phone" type="tel" inputmode="tel" maxlength="20" autocomplete="tel" required class="form-field" value="<?= e($form['phone']) ?>" aria-invalid="<?= isset($errors['phone']) ? 'true' : 'false' ?>"<?= isset($errors['phone']) ? ' aria-describedby="phone-error"' : '' ?>>
                    <?php if (isset($errors['phone'])): ?><p id="phone-error" class="mt-1 text-xs text-destructive" role="alert"><?= e($errors['phone']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="email" class="text-sm font-medium">Email Address</label>
                    <input id="email" name="email" type="email" maxlength="254" autocomplete="email" class="form-field" value="<?= e($form['email']) ?>" aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>"<?= isset($errors['email']) ? ' aria-describedby="email-error"' : '' ?>>
                    <?php if (isset($errors['email'])): ?><p id="email-error" class="mt-1 text-xs text-destructive" role="alert"><?= e($errors['email']) ?></p><?php endif; ?>
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="room-select" class="text-sm font-medium">Room of Interest *</label>
                    <select id="room-select" name="room_id" class="form-field" required>
                        <?php foreach ($rooms as $room): $counts = occupancy($room); ?>
                            <option value="<?= e($room['id']) ?>"<?= $selectedRoomId === $room['id'] ? ' selected' : '' ?>><?= e($room['name']) ?> (<?= e($counts['available']) ?> available)</option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['room_id'])): ?><p class="mt-1 text-xs text-destructive" role="alert"><?= e($errors['room_id']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="bed-select" class="text-sm font-medium">Bedspace of Interest</label>
                    <select id="bed-select" name="bedspace_id" class="form-field"<?= $availableBeds === [] ? ' disabled' : '' ?>>
                        <option value="">Any available</option>
                        <?php foreach ($availableBeds as $bed): ?>
                            <option value="<?= e($bed['id']) ?>"<?= $selectedBedId === $bed['id'] ? ' selected' : '' ?>><?= e($bed['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['bedspace_id'])): ?><p class="mt-1 text-xs text-destructive" role="alert"><?= e($errors['bedspace_id']) ?></p><?php endif; ?>
                </div>
            </div>
            <div>
                <label for="message" class="text-sm font-medium">Message</label>
                <textarea id="message" name="message" rows="4" maxlength="1000" class="form-field"><?= e($form['message']) ?></textarea>
                <?php if (isset($errors['message'])): ?><p class="mt-1 text-xs text-destructive" role="alert"><?= e($errors['message']) ?></p><?php endif; ?>
            </div>
            <p class="text-xs text-muted-foreground">* Required fields</p>
            <button type="submit" class="btn-primary w-full">Send Inquiry</button>
            <?php if ($submissionStatus === 'success'): ?>
                <p role="status" class="flex gap-2 rounded-xl border border-success/50 bg-card p-4 text-sm"><span class="text-success" aria-hidden="true">✓</span><?= e($submissionMessage) ?></p>
            <?php elseif ($submissionStatus === 'error'): ?>
                <p role="alert" class="rounded-xl border border-destructive/50 bg-card p-4 text-sm text-destructive"><?= e($submissionMessage) ?></p>
            <?php endif; ?>
        </form>
    </div>
</section>
