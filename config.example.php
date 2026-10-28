<?php
declare(strict_types=1);

return [
    'db' => [
        'driver' => 'sqlite',
        'path' => __DIR__ . '/storage/mina.sqlite', // Di luar document root public.
    ],
    'app_key' => 'GANTI_DENGAN_64_KARAKTER_HEX_ACAK',
    'base_url' => 'http://localhost:4173', // Tanpa slash terakhir; boleh termasuk subfolder.
    'timezone' => 'Asia/Jakarta',
];
