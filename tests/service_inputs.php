<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/services.php';
require dirname(__DIR__) . '/app/icons.php';
foreach (['081234567890', '6281234567890', '+62 (812) 3456-7890'] as $input) {
    if (normalize_wa_phone($input) !== '6281234567890') throw new RuntimeException('Normalisasi nomor salah.');
}
foreach (['', '0812abc345', 'https://wa.me/6281234567890', '0812#345', '+1 234567890', '0812+345', '0008123456'] as $input) {
    if (normalize_wa_phone($input) !== null) throw new RuntimeException('Nomor invalid diterima.');
}
$message = "Assalamualaikum, Ibu & RT+12?\nNama: 🌿 #Mina";
$record = ['target_type' => 'whatsapp', 'wa_phone' => '6281234567890', 'wa_message' => $message, 'url' => null];
$link = service_url($record);
parse_str(parse_url($link, PHP_URL_QUERY), $query);
if ($query['text'] !== $message) throw new RuntimeException('Pesan berubah setelah encoding.');
$legacy = import_legacy_destination('https://wa.me/081234567890 ?text=Ibu%20%26%20RT%2B12');
if ($legacy['wa_phone'] !== '6281234567890' || $legacy['wa_message'] !== 'Ibu & RT+12' || $legacy['url'] !== null) throw new RuntimeException('Migrasi WA salah.');
$external = 'https://example.test/app?id=1&ref=2';
if (service_url(import_legacy_destination($external)) !== $external) throw new RuntimeException('URL website berubah.');
foreach (service_icons() as $key => $icon) {
    if (legacy_icon_key($icon[1]) !== $key || !str_contains(service_icon($key), $icon[1])) throw new RuntimeException('Ikon tidak konsisten.');
}
echo "PASS: phone normalization/rejection, message encoding, legacy parsing, website preservation, emoji icon catalog.\n";
