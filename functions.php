<?php
declare(strict_types=1);

function e(string|int|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function occupancy(array $room): array
{
    $occupied = count(array_filter($room['bedspaces'], static fn (array $bed): bool => $bed['status'] === 'occupied'));
    $available = count(array_filter($room['bedspaces'], static fn (array $bed): bool => $bed['status'] === 'available'));

    return [
        'total' => count($room['bedspaces']),
        'occupied' => $occupied,
        'available' => $available,
    ];
}

function rate_label(?int $rate): string
{
    return $rate === null ? 'Rate on inquiry' : '₱' . number_format($rate);
}

function image_url(string $filename): string
{
    return 'assets/images/' . rawurlencode($filename);
}

function catalog_uploaded_image_url(string $id): ?string
{
    if (!preg_match('/^room-[a-z0-9-]+$/D', $id)) {
        return null;
    }
    foreach (['jpg', 'png', 'webp'] as $extension) {
        $filename = $id . '.' . $extension;
        $path = dirname(__DIR__) . '/assets/images/room-uploads/' . $filename;
        if (is_file($path)) {
            return 'assets/images/room-uploads/' . rawurlencode($filename) . '?v=' . filemtime($path);
        }
    }
    return null;
}

function room_image_url(array $room): string
{
    return catalog_uploaded_image_url((string) ($room['id'] ?? ''))
        ?? image_url((string) $room['image']);
}

function bed_image(array $room, array $bed): string
{
    $uploadedImage = catalog_uploaded_image_url((string) ($bed['id'] ?? ''));
    if ($uploadedImage !== null) {
        return $uploadedImage;
    }
    return isset($bed['image']) ? image_url($bed['image']) : room_image_url($room);
}

function section_heading(string $eyebrow, string $title, ?string $accent = null, ?string $sub = null, bool $center = true): void
{
    ?>
    <div class="<?= $center ? 'mx-auto max-w-2xl text-center' : '' ?>">
        <p class="eyebrow"><?= e($eyebrow) ?></p>
        <h2 class="mt-2 text-4xl text-foreground md:text-5xl"><?= e($title) ?><?= $accent !== null ? ' <em class="text-primary">' . e($accent) . '</em>' : '' ?></h2>
        <?php if ($sub !== null): ?><p class="mt-3 text-muted-foreground"><?= e($sub) ?></p><?php endif; ?>
    </div>
    <?php
}

function icon(string $name, string $class = 'h-5 w-5'): void
{
    $paths = [
        'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7H10v7H4a1 1 0 0 1-1-1z"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'close' => '<path d="m18 6-12 12"/><path d="m6 6 12 12"/>',
        'menu' => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
        'message' => '<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/>',
        'shield' => '<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/><path d="m9 12 2 2 4-4"/>',
        'wifi' => '<path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><path d="M12 20h.01"/>',
        'droplets' => '<path d="M7 16.3A4 4 0 0 1 3.7 10L10 3l2.3 2.6"/><path d="M16 16.3A4 4 0 0 1 12.7 10L19 3l2.3 2.6A4 4 0 0 1 18 16.3"/><path d="M12 21h.01"/>',
        'zap' => '<path d="m13 2-3 8h7l-6 12 2-9H6l7-11z"/>',
        'shirt' => '<path d="m16 4 4 2 2 5-4 2-2-3v12H8V10l-2 3-4-2 2-5 4-2a4 4 0 0 0 8 0z"/>',
        'utensils' => '<path d="M3 2v7a4 4 0 0 0 4 4h1"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v8h5z"/><path d="M18.5 15v7"/>',
        'cctv' => '<path d="M13 7 5 3 2 8l8 4"/><path d="m8 12 8-4 5 3-8 4"/><path d="M13 15v6"/><path d="M9 21h8"/><circle cx="18" cy="6" r="2"/>',
        'pin' => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'navigation' => '<path d="m3 11 18-8-8 18-2-8-8-2z"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.2 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72l.45 3.1a2 2 0 0 1-.57 1.7L7.1 10.4a16 16 0 0 0 6 6l1.88-1.88a2 2 0 0 1 1.7-.57l3.1.45a2 2 0 0 1 1.72 2z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    ];
    ?>
    <svg class="<?= e($class) ?>" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $paths[$name] ?? $paths['home'] ?></svg>
    <?php
}
