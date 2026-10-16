<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/services.php';
require dirname(__DIR__) . '/app/icons.php';

$pdo = db();
$definitions = $pdo->query('SHOW COLUMNS FROM services')->fetchAll();
$columns = array_column($definitions, 'Field');
$nullability = array_column($definitions, 'Null', 'Field');
$rows = $pdo->query('SELECT * FROM services ORDER BY id')->fetchAll();
$plans = [];
foreach ($rows as $row) {
    if (($row['target_type'] ?? null) !== null) continue;
    $plans[] = ['id' => $row['id'], 'icon' => legacy_icon_key($row['icon'])] + import_legacy_destination($row['url']);
}
if (!$plans && !array_diff(['target_type', 'wa_phone', 'wa_message'], $columns)
    && ($nullability['target_type'] ?? '') === 'NO' && ($nullability['url'] ?? '') === 'YES') {
    echo "Migrasi sudah diterapkan.\n"; exit;
}
$dir = dirname(__DIR__) . '/.local/backups';
if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('Tidak dapat membuat direktori backup.');
$backup = $dir . '/services-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.json';
$file = fopen($backup, 'x');
if (!$file) throw new RuntimeException('Tidak dapat membuat backup.');
chmod($backup, 0600);
$json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
if (fwrite($file, $json) !== strlen($json)) throw new RuntimeException('Backup tidak lengkap; migrasi dibatalkan.');
fclose($file);
foreach (['target_type' => 'VARCHAR(12) NULL', 'wa_phone' => 'VARCHAR(15) NULL', 'wa_message' => 'TEXT NULL'] as $name => $type) {
    if (!in_array($name, $columns, true)) $pdo->exec("ALTER TABLE services ADD COLUMN $name $type");
}
$pdo->exec('ALTER TABLE services MODIFY url TEXT NULL');
$pdo->beginTransaction();
try {
    $q = $pdo->prepare('UPDATE services SET icon = ?, target_type = ?, wa_phone = ?, wa_message = ?, url = ? WHERE id = ? AND target_type IS NULL');
    foreach ($plans as $plan) $q->execute([$plan['icon'], $plan['target_type'], $plan['wa_phone'], $plan['wa_message'], $plan['url'], $plan['id']]);
    $pdo->commit();
} catch (Throwable $error) { $pdo->rollBack(); throw $error; }
$pdo->exec("ALTER TABLE services MODIFY target_type VARCHAR(12) NOT NULL DEFAULT 'whatsapp'");
echo count($plans) . " tombol dimigrasikan. Backup: $backup\n";
