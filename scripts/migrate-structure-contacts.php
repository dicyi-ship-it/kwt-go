<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
if (db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
    db()->query('SELECT structure_key, name, wa_phone FROM structure_contacts LIMIT 0');
    exit("Tabel kontak SQLite siap.\n");
}
try {
    db()->query('SELECT structure_key, wa_phone FROM structure_contacts LIMIT 0');
} catch (PDOException $error) {
    if ($error->getCode() !== '42S02') throw $error;
    db()->exec(file_get_contents(dirname(__DIR__) . '/database/structure-contacts.sql'));
}
try {
    db()->query('SELECT name FROM structure_contacts LIMIT 0');
} catch (PDOException $error) {
    if ($error->getCode() !== '42S22') throw $error;
    db()->exec("ALTER TABLE structure_contacts ADD COLUMN name VARCHAR(120) NOT NULL DEFAULT ''");
}
echo "Tabel kontak struktur siap.\n";
