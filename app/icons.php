<?php
declare(strict_types=1);

function service_icons(): array
{
    return [
        'logo_tangerang' => ['Kota Tangerang', 'assets/kota-tangerang.png'],
        'logo_polri' => ['Polri', 'assets/polri.png'],
        'logo_pmi' => ['PMI', 'assets/pmi.svg'],
        'logo_damkar' => ['Damkar', 'assets/damkar.png'],
        'logo_puskesmas' => ['Puskesmas', 'assets/puskesmas-panunggangan-barat.jpg'],
        'home' => ['Rumah', '🏠'],
        'alert' => ['Darurat', '🚨'],
        'mosque' => ['Masjid', '🕌'],
        'broom' => ['Kebersihan', '🧹'],
        'people' => ['Sosial', '🤝'],
        'shop' => ['UMKM', '🛍️'],
        'baby' => ['Anak', '👶'],
        'books' => ['Perpustakaan', '📚'],
        'plant' => ['Tanaman', '🌾'],
        'park' => ['Taman', '🎡'],
        'shield' => ['Keamanan', '🛡️'],
        'bolt' => ['Siaga', '⚡'],
        'health' => ['Kesehatan', '💉'],
        'fire' => ['Pemadam', '🔥'],
        'megaphone' => ['Pengumuman', '📣'],
        'chat' => ['Saran / pesan', '💬'],
        'phone' => ['Aplikasi', '📱'],
        'care' => ['Pendampingan', '⚰️'],
        'hospital' => ['Puskesmas', '🏥'],
        'ambulance' => ['Ambulan', '🚑'],
    ];
}

function service_icon(string $key): string
{
    $icon = service_icons()[$key] ?? service_icons()['home'];
    if (str_starts_with($icon[1], 'assets/')) {
        return '<img src="' . htmlspecialchars(url($icon[1]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" width="38" height="38" alt="" class="service-logo">';
    }
    return '<span class="service-emoji" aria-hidden="true">' . htmlspecialchars($icon[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
}

function legacy_icon_key(string $value): string
{
    if (isset(service_icons()[$value])) return $value;
    foreach (service_icons() as $key => $icon) if ($icon[1] === $value) return $key;
    throw new RuntimeException('Ikon lama belum memiliki padanan: ' . $value);
}
