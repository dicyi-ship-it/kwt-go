<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/umkm-photos.php';
$pdo = db();
$sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
try { $pdo->query('SELECT id, filename, sort_order, is_sample FROM umkm_photos LIMIT 0'); }
catch (PDOException $error) { $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/umkm-photos' . ($sqlite ? '.sqlite' : '') . '.sql')); }
if (!in_array('--import-existing', $argv, true)) { echo "Tabel foto UMKM siap.\n"; exit; }
$businesses = $pdo->query('SELECT * FROM umkm ORDER BY id')->fetchAll();
$before = $pdo->query('SELECT * FROM umkm_photos')->fetchAll();
$backupDir = dirname(__DIR__) . '/.local/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true)) throw new RuntimeException('Backup gagal.');
$backup = $backupDir . '/umkm-photo-import-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.json';
$json = json_encode(['umkm' => $businesses, 'photos' => $before], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
if (file_put_contents($backup, $json, LOCK_EX) !== strlen($json)) throw new RuntimeException('Backup gagal.');
$folders = ['Dapur Mina Rasa' => 'dapur-mina-rasa', 'Kreasi Jahit Ilhami' => 'kreasi-jahit-ilhami', 'Kebun Hijau Mina' => 'kebun-hijau-mina', 'Bersih Wangi Laundry' => 'bersih-wangi-laundry', 'Kriya Anyam Panbar' => 'kriya-anyam-panbar'];
$created = [];
$imported = 0;
$pdo->beginTransaction();
try {
    $insert = $pdo->prepare('INSERT INTO umkm_photos (umkm_id, filename, sort_order, is_sample) VALUES (?, ?, ?, ?)');
    foreach ($businesses as $business) {
        $id = (int) $business['id'];
        if (umkm_photos($id)) continue;
        $existing = glob(umkm_photo_dir($id) . '/*.jpg') ?: [];
        foreach ($existing as $order => $path) {
            $filename = basename($path);
            umkm_photo_path($id, $filename);
            $insert->execute([$id, $filename, $order, 0]);
            $imported++;
        }
        if (empty($business['is_sample']) || !isset($folders[$business['name']])) continue;
        $folder = $folders[$business['name']];
        for ($number = 1; $number <= 5 && count($existing) + $number <= 6; $number++) {
            $source = dirname(__DIR__) . '/public/assets/umkm-samples/' . $folder . '/' . $number . '.jpg';
            if (!is_file($source)) throw new RuntimeException('Gambar contoh tidak tersedia: ' . $source);
            $dir = umkm_photo_dir($id);
            if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('Folder foto gagal dibuat.');
            $filename = md5($folder . '/' . $number) . '.jpg';
            $path = umkm_photo_path($id, $filename);
            if (is_file($path)) throw new RuntimeException('File tujuan sudah ada tanpa catatan database: ' . $path);
            $created[] = $path;
            if (!copy($source, $path)) throw new RuntimeException('Penyalinan foto gagal.');
            $insert->execute([$id, $filename, count($existing) + $number - 1, 1]);
            $imported++;
        }
    }
    $pdo->commit();
} catch (Throwable $error) {
    $pdo->rollBack();
    foreach ($created as $path) if (is_file($path)) unlink($path);
    throw $error;
}
echo $imported . " foto didaftarkan ke database. Backup: " . $backup . "\n";
