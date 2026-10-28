<div id="room-modal-backdrop" class="fixed inset-0 z-50 hidden place-items-center bg-foreground/50 p-4" aria-hidden="true">
    <div id="room-modal" role="dialog" aria-modal="true" aria-labelledby="room-modal-title" aria-describedby="room-modal-description" class="relative grid max-h-[88vh] w-full max-w-5xl grid-rows-[minmax(10rem,32vh)_minmax(0,1fr)] overflow-hidden rounded-3xl bg-card shadow-soft md:grid-cols-2 md:grid-rows-1" tabindex="-1">
        <button id="room-modal-close" type="button" aria-label="Close details" class="absolute right-4 top-4 z-10 rounded-full bg-card p-2 text-primary"><?php icon('close', 'h-5 w-5'); ?></button>
        <div class="min-h-0 overflow-hidden">
            <img id="room-modal-image" src="" alt="" class="h-full w-full object-cover">
        </div>
        <div class="min-h-0 min-w-0 overflow-y-auto overscroll-contain p-5 sm:p-7">
            <div class="mb-6 pr-10">
                <span id="room-modal-badge" class="rounded-full bg-success px-3 py-1 text-[0.65rem] font-medium uppercase tracking-widest text-primary-foreground"></span>
                <h2 id="room-modal-title" class="mt-3 font-serif text-3xl sm:text-4xl"></h2>
                <p id="room-modal-description" class="mt-2 text-muted-foreground"></p>
                <p id="room-modal-rate" class="mt-4 font-serif text-3xl text-primary"></p>
            </div>
            <div class="space-y-5">
                <dl id="room-modal-counts" class="grid grid-cols-3 gap-2 text-center"></dl>
                <div>
                    <p class="mb-2 text-sm font-medium">Select a bedspace to view its details</p>
                    <div id="room-modal-beds" class="grid gap-2 sm:grid-cols-2"></div>
                </div>
                <div id="room-modal-bed-preview" class="overflow-hidden rounded-2xl border border-border bg-muted">
                    <img id="room-modal-bed-image" src="" alt="" class="aspect-[16/9] w-full object-cover">
                    <div class="p-4">
                        <p id="room-modal-bed-status" class="text-xs uppercase tracking-widest"></p>
                        <h3 id="room-modal-bed-title" class="mt-1 text-xl font-semibold"></h3>
                        <p id="room-modal-bed-rate" class="mt-1 text-sm text-muted-foreground"></p>
                    </div>
                </div>
                <button id="room-modal-inquire" type="button" class="btn-primary w-full"></button>
            </div>
        </div>
    </div>
</div>

<div id="gallery-modal" class="fixed inset-0 z-50 hidden place-items-center bg-foreground/80 p-4" role="dialog" aria-modal="true" aria-label="Gallery photo viewer" aria-hidden="true">
    <button type="button" data-gallery-close aria-label="Close photo" class="absolute right-5 top-5 rounded-full bg-card p-2 text-primary"><?php icon('close'); ?></button>
    <div class="absolute inset-x-5 top-1/2 z-10 flex -translate-y-1/2 justify-between">
        <button type="button" data-gallery-lightbox-move="-1" aria-label="Previous photo" class="grid h-11 w-11 place-items-center rounded-full bg-card text-primary shadow-soft"><?php icon('chevron-left'); ?></button>
        <button type="button" data-gallery-lightbox-move="1" aria-label="Next photo" class="grid h-11 w-11 place-items-center rounded-full bg-card text-primary shadow-soft"><?php icon('chevron-right'); ?></button>
    </div>
    <figure class="max-w-full" tabindex="-1">
        <img id="gallery-modal-image" src="" alt="" class="max-h-[78vh] max-w-full rounded-2xl object-contain">
        <figcaption id="gallery-modal-caption" class="mt-3 text-center text-lg font-medium text-white"></figcaption>
    </figure>
</div>
