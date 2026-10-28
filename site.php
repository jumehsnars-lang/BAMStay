<?php
declare(strict_types=1);

/**
 * Site content is kept separate from the presentation layer so it can later
 * be loaded from a database without changing the templates.
 *
 * @return list<array{id: string, label: string, status: string, image?: string}>
 */
/**
 * @param list<'available'|'occupied'|'maintenance'> $statuses
 * @param array<string, string> $images
 * @return list<array{id: string, label: string, status: string, image?: string}>
 */
function bamstay_beds(string $roomId, array $statuses, array $images = []): array
{
    $beds = [];
    foreach ($statuses as $index => $status) {
        $letter = chr(65 + $index);
        $bed = [
            'id' => $roomId . '-b' . ($index + 1),
            'label' => 'Bed ' . $letter,
            'status' => $status,
        ];
        if (isset($images[$letter])) {
            $bed['image'] = $images[$letter];
        }
        $beds[] = $bed;
    }

    return $beds;
}

$business = [
    'name' => 'BAMStay',
    'tagline' => "More Than a Room, It's Your Home.",
    'tenant_login_url' => 'login.php',
    'address' => 'Bucana Elementary School, 3J8G+3GF',
    'municipality' => 'Nasugbu',
    'province' => 'Batangas',
    'map_query' => 'Bucana Elementary School, 3J8G+3GF, Nasugbu, Batangas',
    'map_embed_query' => '14.0651961,120.6262967',
    'landmarks' => [
        'Batangas State University - ARASOF Nasugbu',
        'Nasugbu Public Market',
        'public transport terminals',
        'fast-food restaurants',
        'churches',
    ],
];

/** @var list<array{id: string, name: string, image: string, description: string, features: list<string>, monthly_rate: ?int, bedspaces: list<array{id: string, label: string, status: string, image?: string}>}> $rooms */
$rooms = [
    [
        'id' => 'room-1',
        'name' => 'Room 1',
        'image' => 'room1.jpg',
        'description' => 'A Bright and Cozy Bunk Bed Room Beside the Balcony',
        'features' => ['Ceiling Fan', 'Storage Space', 'Electrical Outlet', 'Natural Lighting'],
        'monthly_rate' => 1800,
        'bedspaces' => bamstay_beds('room-1', ['available', 'available', 'available', 'available', 'available', 'available']),
    ],
    [
        'id' => 'room-2',
        'name' => 'Room 2',
        'image' => 'room2-real.png',
        'description' => 'A Spacious and Bright Room Beside the Window and Balcony',
        'features' => ['Ceiling Fan', 'Study Table', 'Electrical Outlet', 'Natural Lighting'],
        'monthly_rate' => 1800,
        'bedspaces' => bamstay_beds('room-2', ['available', 'available', 'available', 'available'], [
            'A' => 'room2-bed-a.png',
            'B' => 'room2-bed-b.png',
            'C' => 'room2-bed-c.png',
            'D' => 'room2-bed-d.png',
        ]),
    ],
    [
        'id' => 'room-3',
        'name' => 'Room 3',
        'image' => 'room3-real.png',
        'description' => 'A Cozy and Comfortable Room Near the Shared Kitchen and Living Room',
        'features' => ['Ceiling Fan', 'Storage Space', 'Study Table'],
        'monthly_rate' => 1800,
        'bedspaces' => bamstay_beds('room-3', ['available', 'available', 'available', 'available', 'available', 'available', 'available', 'available'], [
            'A' => 'room3-bed-a.png',
            'B' => 'room3-bed-b.png',
            'C' => 'room3-bed-c.png',
            'D' => 'room3-bed-d.png',
            'E' => 'room3-bed-e.png',
            'F' => 'room3-bed-f.png',
            'G' => 'room3-bed-g.png',
            'H' => 'room3-bed-h.png',
        ]),
    ],
    [
        'id' => 'room-4',
        'name' => 'Room 4',
        'image' => 'room4-real.png',
        'description' => 'A cozy and minimalist space designed for a simple, comfortable, and relaxing living experience.',
        'features' => [],
        'monthly_rate' => 1800,
        'bedspaces' => bamstay_beds('room-4', ['available', 'available', 'available', 'available'], [
            'A' => 'room4-bed-a.png',
            'B' => 'room4-bed-b.png',
            'C' => 'room4-bed-c.png',
            'D' => 'room4-bed-d.png',
        ]),
    ],
    [
        'id' => 'room-5',
        'name' => 'Room 5',
        'image' => 'room1.jpg',
        'description' => 'A Cozy and Convenient Room Near the Shared Kitchen.',
        'features' => [],
        'monthly_rate' => 1800,
        'bedspaces' => bamstay_beds('room-5', ['available', 'available', 'available', 'available']),
    ],
];

$highlights = [
    ['icon' => 'shield', 'title' => 'Safe & Secure', 'description' => 'CCTV and secured gate'],
    ['icon' => 'wifi', 'title' => 'Free WiFi', 'description' => 'Stay connected for study'],
    ['icon' => 'droplets', 'title' => '24/7 Water', 'description' => 'Reliable water supply'],
    ['icon' => 'zap', 'title' => 'Unli-Electric', 'description' => 'Included in your rent'],
];

$facilities = [
    ['icon' => 'wifi', 'title' => 'Free WiFi', 'description' => 'Internet access for classes, research and staying in touch.'],
    ['icon' => 'zap', 'title' => 'Unlimited Electricity', 'description' => 'Power use included in the monthly rate.'],
    ['icon' => 'droplets', 'title' => '24/7 Water', 'description' => 'Water available any time of day.'],
    ['icon' => 'shirt', 'title' => 'Laundry Area', 'description' => 'A shared space to wash and dry clothes.'],
    ['icon' => 'utensils', 'title' => 'Shared Kitchen', 'description' => 'Prepare your own meals in a clean kitchen.'],
    ['icon' => 'cctv', 'title' => 'CCTV Security', 'description' => 'Monitored common areas for peace of mind.'],
];

$gallery = [
    ['src' => 'bamstay-exterior.png', 'alt' => 'Exterior of the BAMStay dormitory', 'label' => 'Exterior'],
    ['src' => 'gallery-living-room-tv.png', 'alt' => 'BAMStay living room with television and wooden seating', 'label' => 'Living Room'],
    ['src' => 'gallery-living-room-seating.png', 'alt' => 'Seating area in the BAMStay living room', 'label' => 'Living Room'],
    ['src' => 'gallery-hallway.png', 'alt' => 'Hallway inside BAMStay', 'label' => 'Hallway'],
    ['src' => 'gallery-kitchen.png', 'alt' => 'Kitchen inside BAMStay', 'label' => 'Kitchen'],
    ['src' => 'gallery-balcony.png', 'alt' => 'BAMStay balcony overlooking the street', 'label' => 'Balcony'],
    ['src' => 'gallery-hallway-2.png', 'alt' => 'Interior corridor at BAMStay', 'label' => 'Hallway'],
    ['src' => 'gallery-stairs.png', 'alt' => 'Stairway inside BAMStay', 'label' => 'Stairs'],
];

$testimonials = [
    [
        'name' => 'Sample placeholder',
        'rating' => null,
        'text' => 'Add an approved tenant testimonial here.',
        'placeholder' => true,
    ],
];
$announcements = [];
