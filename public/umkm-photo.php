<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/umkm-photos.php';

$id = $_GET['usaha'] ?? '';
$photo = $_GET['foto'] ?? '';
if (!is_string($id) || !preg_match('/^[1-9][0-9]{0,9}$/D', $id) || (float) $id > 4294967295
    || !is_string($photo) || !preg_match('/^[a-f0-9]{32}\.jpg$/D', $photo)) {
    http_response_code(404); exit;
}
$q = db()->prepare('SELECT p.id FROM umkm_photos p JOIN umkm u ON u.id = p.umkm_id WHERE p.umkm_id = ? AND p.filename = ?');
$q->execute([$id, $photo]);
$path = umkm_photo_path((int) $id, $photo);
if (!$q->fetch() || !is_file($path)) { http_response_code(404); exit; }
header('Content-Type: image/jpeg');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . filesize($path));
readfile($path);
