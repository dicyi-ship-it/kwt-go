<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';

$destination = $argv[1] ?? '';
$parent = realpath(dirname($destination));
$public = str_replace('\\', '/', realpath(dirname(__DIR__) . '/public'));
if ($destination === '' || $parent === false || str_starts_with(strtolower(str_replace('\\', '/', $parent)) . '/', strtolower($public) . '/')) {
    exit("Pemakaian: php scripts/migrate-to-sqlite.php PATH_DI_LUAR_PUBLIC\nFolder tujuan harus sudah tersedia.\n");
}
$source = db();
if ($source->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') throw new RuntimeException('Sumber migrasi harus MySQL.');
// Exclusive creation prevents accidentally overwriting an existing database.
$file = fopen($destination, 'x');
if ($file === false) throw new RuntimeException('File tujuan sudah ada atau tidak dapat dibuat.');
fclose($file);
$target = new PDO('sqlite:' . $destination, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$target->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sqlite.sql'));
$source->beginTransaction();
$target->beginTransaction();
try {
    foreach (['admins', 'services', 'visitors', 'visitor_days', 'login_attempts', 'structure_contacts', 'umkm'] as $table) {
        $rows = $source->query('SELECT * FROM ' . $table)->fetchAll();
        foreach ($rows as $row) {
            $columns = array_keys($row);
            $query = $target->prepare('INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')');
            $query->execute(array_values($row));
        }
        $copied = $target->query('SELECT * FROM ' . $table)->fetchAll();
        // Old MySQL installations have no officer name column yet.
        if ($table === 'structure_contacts') foreach ($rows as &$row) $row['name'] ??= '';
        unset($row);
        $normalize = static function (array $records): array {
            $result = [];
            foreach ($records as $record) { ksort($record); $result[] = json_encode(array_map(static fn($value) => $value === null ? null : (string) $value, $record), JSON_THROW_ON_ERROR); }
            sort($result);
            return $result;
        };
        if ($normalize($rows) !== $normalize($copied)) throw new RuntimeException('Verifikasi data gagal: ' . $table);
        echo $table . ': ' . count($rows) . " baris terverifikasi\n";
    }
    if ($target->query('PRAGMA foreign_key_check')->fetchAll()) throw new RuntimeException('Relasi data tidak valid.');
    $target->commit();
    $source->rollBack();
    echo "Migrasi selesai. MySQL tidak diubah. Ubah config.local.php setelah verifikasi.\n";
} catch (Throwable $error) {
    if ($target->inTransaction()) $target->rollBack();
    if ($source->inTransaction()) $source->rollBack();
    throw $error;
}
