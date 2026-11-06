<?php
declare(strict_types=1);

function umkm_photo_dir(int $id): string
{
    if ($id < 1) throw new InvalidArgumentException('ID usaha tidak valid.');
    return dirname(__DIR__) . '/storage/umkm/' . $id;
}

function umkm_photos(int $id): array
{
    $q = db()->prepare('SELECT filename FROM umkm_photos WHERE umkm_id = ? ORDER BY sort_order, id');
    $q->execute([$id]);
    return $q->fetchAll(PDO::FETCH_COLUMN);
}

function umkm_photo_path(int $id, string $photo): string
{
    if (!preg_match('/^[a-f0-9]{32}\.jpg$/D', $photo)) throw new InvalidArgumentException('Foto tidak valid.');
    return umkm_photo_dir($id) . '/' . $photo;
}

function umkm_gallery(array $business): array
{
    $q = db()->prepare('SELECT filename, is_sample FROM umkm_photos WHERE umkm_id = ? ORDER BY sort_order, id');
    $q->execute([$business['id']]);
    $photos = [];
    foreach ($q->fetchAll() as $index => $row) {
        $photos[] = ['src' => url('umkm-photo.php?usaha=' . $business['id'] . '&foto=' . $row['filename']),
            'alt' => ($row['is_sample'] ? 'Ilustrasi ' : 'Foto ') . ($index + 1) . ': ' . $business['name'], 'sample' => (bool) $row['is_sample'], 'file' => $row['filename']];
    }
    return $photos;
}

function delete_umkm_photo(int $id, string $photo): void
{
    $path = umkm_photo_path($id, $photo);
    $q = db()->prepare('DELETE FROM umkm_photos WHERE umkm_id = ? AND filename = ?');
    $q->execute([$id, $photo]);
    if (!$q->rowCount()) throw new InvalidArgumentException('Foto tidak ditemukan.');
    if (is_file($path) && !unlink($path)) error_log('Gagal membersihkan foto UMKM: ' . $path);
}

function move_umkm_photo(int $id, string $photo, string $move): void
{
    umkm_photo_path($id, $photo);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('SELECT id FROM umkm WHERE id = ?' . ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : ''));
        $q->execute([$id]);
        if (!$q->fetch()) throw new InvalidArgumentException('Usaha tidak ditemukan.');
        $photos = umkm_photos($id);
        $index = array_search($photo, $photos, true);
        if ($index === false) throw new InvalidArgumentException('Foto tidak ditemukan.');
        $target = match ($move) {
            'photo_up' => max(0, $index - 1),
            'photo_down' => min(count($photos) - 1, $index + 1),
            'photo_cover' => 0,
            default => throw new InvalidArgumentException('Urutan tidak valid.'),
        };
        array_splice($photos, $index, 1);
        array_splice($photos, $target, 0, [$photo]);
        $q = $pdo->prepare('UPDATE umkm_photos SET sort_order = ? WHERE umkm_id = ? AND filename = ?');
        foreach ($photos as $order => $filename) $q->execute([$order, $id, $filename]);
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function upload_umkm_photos(int $id, array $files): void
{
    if (!extension_loaded('gd')) throw new RuntimeException('Server memerlukan ekstensi GD untuk mengolah foto.');
    if (!isset($files['error']) || !is_array($files['error'])) throw new RuntimeException('Pilih foto usaha terlebih dahulu.');
    $count = count($files['error']);
    $dir = umkm_photo_dir($id);
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('Folder foto tidak dapat dibuat.');
    $lock = fopen($dir . '/.lock', 'c');
    if (!$lock) throw new RuntimeException('Folder foto tidak dapat dikunci.');
    $created = [];
    $pdo = db();
    try {
        if (!flock($lock, LOCK_EX)) throw new RuntimeException('Foto sedang diproses. Coba kembali.');
        $pdo->beginTransaction();
        $q = $pdo->prepare('SELECT id FROM umkm WHERE id = ?' . ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : ''));
        $q->execute([$id]);
        if (!$q->fetch()) throw new InvalidArgumentException('Usaha tidak ditemukan.');
        $existing = count(umkm_photos($id));
        $orderQuery = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM umkm_photos WHERE umkm_id = ?');
        $orderQuery->execute([$id]);
        $order = (int) $orderQuery->fetchColumn();
        if ($count < 1 || $count + $existing > 6) throw new RuntimeException('Maksimal 6 foto per usaha.');
        foreach ($files['error'] as $index => $error) {
            if ($error !== UPLOAD_ERR_OK) throw new RuntimeException('Unggah foto gagal. Pilih ulang foto dan coba kembali.');
            $tmp = $files['tmp_name'][$index] ?? null;
            if (!is_string($tmp) || !is_uploaded_file($tmp)) throw new RuntimeException('Berkas unggahan tidak valid.');
            if (filesize($tmp) > 5 * 1024 * 1024) throw new RuntimeException('Setiap foto maksimal 5 MB.');
            $info = @getimagesize($tmp);
            if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) throw new RuntimeException('Foto harus berupa JPG, PNG, atau WebP.');
            if ($info[0] * $info[1] > 8000000) throw new RuntimeException('Resolusi foto maksimal 8 megapiksel.');
            $orientation = 1;
            if ($info[2] === IMAGETYPE_JPEG) {
                if (!function_exists('exif_read_data')) throw new RuntimeException('Server memerlukan ekstensi EXIF untuk membaca orientasi foto JPEG.');
                $exif = @exif_read_data($tmp, 'IFD0', true);
                $orientation = (int) ($exif['IFD0']['Orientation'] ?? 1);
            }
            $image = @imagecreatefromstring(file_get_contents($tmp));
            if (!$image) throw new RuntimeException('Foto rusak atau tidak dapat dibaca.');
            try {
                if (in_array($orientation, [2, 5, 7], true)) imageflip($image, IMG_FLIP_HORIZONTAL);
                if ($orientation === 4) imageflip($image, IMG_FLIP_VERTICAL);
                $angle = match ($orientation) { 3 => 180, 5, 8 => 90, 6, 7 => -90, default => 0 };
                if ($angle !== 0) {
                    $rotated = imagerotate($image, $angle, 0);
                    if (!$rotated) throw new RuntimeException('Orientasi foto tidak dapat diperbaiki.');
                    imagedestroy($image);
                    $image = $rotated;
                }
                $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
                if (!$canvas) throw new RuntimeException('Foto tidak dapat diolah.');
                imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
                imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                imagedestroy($image);
                $image = $canvas;
                $path = umkm_photo_path($id, bin2hex(random_bytes(16)) . '.jpg');
                $created[] = $path;
                // Re-encode uploads so user-supplied executable content and metadata are not served.
                if (!imagejpeg($image, $path, 85)) throw new RuntimeException('Foto tidak dapat disimpan.');
                $q = $pdo->prepare('INSERT INTO umkm_photos (umkm_id, filename, sort_order, is_sample) VALUES (?, ?, ?, 0)');
                $q->execute([$id, basename($path), $order++]);
            } finally {
                imagedestroy($image);
            }
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($created as $path) if (is_file($path)) unlink($path);
        throw $error;
    } finally {
        fclose($lock);
    }
}
