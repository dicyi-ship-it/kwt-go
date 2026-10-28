<?php
declare(strict_types=1);

// The sole owner of WhatsApp normalization and link construction.
function normalize_wa_phone(string $input): ?string
{
    if (!preg_match('/^\+?[0-9 ()\-.]+$/D', $input)) return null;
    $phone = preg_replace('/[ ()\-.]/', '', ltrim($input, '+'));
    if (str_starts_with($phone, '0')) $phone = '62' . substr($phone, 1);
    return preg_match('/^62[1-9][0-9]{4,12}$/D', $phone) ? $phone : null;
}

function service_url(array $service): string
{
    if ($service['target_type'] === 'whatsapp') {
        return 'https://wa.me/' . $service['wa_phone'] . ($service['wa_message'] !== '' ? '?text=' . rawurlencode($service['wa_message']) : '');
    }
    return $service['url'];
}

function import_legacy_destination(string $url): array
{
    $parts = parse_url($url);
    if (!$parts || strtolower($parts['host'] ?? '') !== 'wa.me') {
        return ['target_type' => 'link', 'wa_phone' => null, 'wa_message' => null, 'url' => $url];
    }
    $phone = normalize_wa_phone(trim($parts['path'] ?? '', " /\t\n\r"));
    if ($phone === null) throw new RuntimeException('Nomor WhatsApp lama tidak dapat dimigrasikan: ' . $url);
    parse_str($parts['query'] ?? '', $query);
    if (isset($query['text']) && !is_string($query['text'])) throw new RuntimeException('Pesan WhatsApp lama tidak valid.');
    return ['target_type' => 'whatsapp', 'wa_phone' => $phone, 'wa_message' => $query['text'] ?? '', 'url' => null];
}

function service_channels(): array
{
    return [
    'administrasi' => ['title' => 'Layanan Administrasi', 'icon' => 'home', 'description' => 'Kenali pengurus RW dan RT, serta akses informasi administrasi warga.', 'detail' => 'Struktur RW 12 & RT 1-6'],
    'ekonomi' => ['title' => 'Layanan Ekonomi', 'icon' => 'shop', 'description' => 'Temukan layanan usaha dan kegiatan ekonomi di lingkungan kita.', 'detail' => 'UMKM & kegiatan warga'],
    'permasyarakatan' => ['title' => 'Layanan Permasyarakatan', 'icon' => 'people', 'description' => 'Akses layanan sosial, kesehatan, keamanan, dan lingkungan.', 'detail' => 'Sosial & lingkungan'],
];
}

function service_channel(array $service): string
{
    return match ($service['icon']) {
        'shop', 'plant' => 'ekonomi',
        'home', 'megaphone', 'chat', 'phone' => 'administrasi',
        default => 'permasyarakatan',
    };
}
