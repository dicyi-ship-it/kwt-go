<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'mina_go',
        'user' => 'mina_go',
        'password' => 'ISI_PASSWORD_DATABASE',
        'socket' => '', // Opsional: socket lokal MySQL sebagai pengganti host/port.
    ],
    'app_key' => 'GANTI_DENGAN_64_KARAKTER_HEX_ACAK',
    'base_url' => 'http://localhost:4173', // Tanpa slash terakhir; boleh termasuk subfolder.
    'timezone' => 'Asia/Jakarta',
];
