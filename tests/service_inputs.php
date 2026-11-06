<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/services.php';
require dirname(__DIR__) . '/app/icons.php';
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/admin.php';
$_POST = ['label' => 'Tangerang Siaga 112', 'icon' => 'bolt', 'target_type' => 'link', 'sort_order' => '12', 'is_active' => '1'];
foreach (['tel:112' => true, 'tel:+6281234567890' => true, 'tel:112?x=1' => false, 'tel:112%0A' => false, 'javascript:alert(1)' => false] as $destination => $valid) {
    $_POST['url'] = $destination;
    [$data, $errors] = validate_service();
    if (($errors === []) !== $valid) throw new RuntimeException('Validasi tujuan telepon salah: ' . $destination);
    if ($valid && service_url($data) !== $destination) throw new RuntimeException('Tujuan telepon berubah.');
}
$_POST = [];
foreach (['logo_tangerang' => 'kota-tangerang.png', 'logo_polri' => 'polri.png', 'logo_pmi' => 'pmi.svg', 'logo_damkar' => 'damkar.png', 'logo_puskesmas' => 'puskesmas-panunggangan-barat.jpg'] as $key => $logo) {
    $_POST = ['label' => 'Template logo', 'icon' => $key, 'target_type' => 'whatsapp', 'wa_phone' => '081234567890', 'sort_order' => '1', 'is_active' => '1'];
    [$data, $errors] = validate_service();
    if ($errors || !str_contains(service_icon($data['icon']), $logo) || service_channel($data) !== 'permasyarakatan') throw new RuntimeException('Template logo tidak dapat dipakai ulang.');
}
$_POST['icon'] = 'logo_unknown';
[, $errors] = validate_service();
if (!$errors) throw new RuntimeException('Template tidak dikenal diterima.');
$_POST = [];
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
echo "PASS: phone normalization/rejection, message encoding, legacy parsing, website preservation, reusable logo and emoji catalog.\n";
