<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
try {
    db()->query('SELECT structure_key, wa_phone FROM structure_contacts LIMIT 0');
} catch (PDOException $error) {
    if ($error->getCode() !== '42S02') throw $error;
    db()->exec(file_get_contents(dirname(__DIR__) . '/database/structure-contacts.sql'));
}
echo "Tabel kontak struktur siap.\n";
