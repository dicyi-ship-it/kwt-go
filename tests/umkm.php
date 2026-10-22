<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/umkm.php';
function check_umkm(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
$valid = ['name' => 'Usaha Uji', 'category' => 'Kuliner', 'description' => 'Deskripsi', 'address' => 'Alamat', 'wa_phone' => '+62 812-3456-7890'];
[$data, $errors] = validate_umkm($valid);
check_umkm(!$errors && $data['wa_phone'] === '6281234567890', 'Phone normalization');
foreach (['name' => '', 'description' => str_repeat('a', 2001), 'wa_phone' => 'https://wa.me/6281234567890', 'category' => ['invalid']] as $key => $value) {
    [, $errors] = validate_umkm(array_replace($valid, [$key => $value]));
    check_umkm((bool) $errors, 'Reject invalid ' . $key);
}
[$data, $errors] = validate_umkm(array_replace($valid, ['wa_phone' => '']));
check_umkm(!$errors && $data['wa_phone'] === '', 'Allow missing phone without fake contact');
echo "PASS: UMKM field validation, phone normalization, optional phone\n";
