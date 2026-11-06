<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
$pdo = db();
$rows = $pdo->query('SELECT * FROM services ORDER BY id')->fetchAll();
$plans = [];
foreach ($rows as $row) {
    if (str_starts_with($row['icon'], 'logo_')) continue;
    $icon = match ($row['url']) {
        'tel:112' => 'logo_tangerang',
        'tel:110' => 'logo_polri',
        'tel:02138951051', 'tel:02155791008' => 'logo_pmi',
        default => match ($row['icon']) {
            'fire' => 'logo_damkar', 'hospital' => 'logo_puskesmas', default => null,
        },
    };
    if ($icon !== null) $plans[] = [$icon, $row['id'], $row['icon']];
}
if (!$plans) { echo "Logo layanan sudah tersimpan.\n"; exit; }
$dir = dirname(__DIR__) . '/.local/backups';
if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('Backup gagal.');
$json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
$backup = $dir . '/service-logos-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.json';
if (file_put_contents($backup, $json, LOCK_EX) !== strlen($json)) throw new RuntimeException('Backup gagal.');
$pdo->beginTransaction();
try {
    $q = $pdo->prepare('UPDATE services SET icon = ? WHERE id = ? AND icon = ?');
    foreach ($plans as $plan) $q->execute($plan);
    $pdo->commit();
} catch (Throwable $error) { $pdo->rollBack(); throw $error; }
echo count($plans) . " logo layanan disimpan. Backup: " . $backup . "\n";
