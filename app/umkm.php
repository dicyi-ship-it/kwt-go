<?php
declare(strict_types=1);
require_once __DIR__ . '/services.php';

function umkm_list(): array
{
    try { return db()->query('SELECT * FROM umkm ORDER BY name, id')->fetchAll(); }
    catch (PDOException $error) {
        if ($error->getCode() !== '42S02') throw $error;
        return [];
    }
}

function validate_umkm(array $input): array
{
    $data = [];
    $errors = [];
    foreach (['name' => ['Nama usaha', 120], 'category' => ['Kategori', 80], 'description' => ['Deskripsi', 2000], 'address' => ['Alamat', 255], 'wa_phone' => ['WhatsApp', 40]] as $key => [$label, $limit]) {
        $value = $input[$key] ?? '';
        if (!is_string($value)) { $errors[] = $label . ' tidak valid.'; $value = ''; }
        $data[$key] = trim($value);
        if (($key !== 'wa_phone' && $data[$key] === '') || mb_strlen($data[$key]) > $limit || str_contains($data[$key], "\0")) $errors[] = $label . ' wajib diisi dan maksimal ' . $limit . ' karakter.';
    }
    if ($data['wa_phone'] !== '') {
        $phone = normalize_wa_phone($data['wa_phone']);
        if ($phone === null) $errors[] = 'Nomor WhatsApp tidak valid. Gunakan format 08 atau +62.';
        else $data['wa_phone'] = $phone;
    }
    $data['is_sample'] = ($input['is_sample'] ?? '') === '1' ? 1 : 0;
    return [$data, $errors];
}
