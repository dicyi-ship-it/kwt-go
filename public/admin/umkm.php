<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require dirname(__DIR__, 2) . '/app/admin.php';
require dirname(__DIR__, 2) . '/app/umkm.php';
require dirname(__DIR__, 2) . '/app/umkm-photos.php';
web_headers();
require_admin();
$pdo = db();
$errors = [];
$photoErrors = [];
$form = ['name' => '', 'category' => '', 'description' => '', 'address' => '', 'wa_phone' => '', 'is_sample' => 0];
$id = '';
$delete = null;
function find_umkm(string $id): array {
    if (!preg_match('/^[1-9][0-9]{0,9}$/D', $id) || (float) $id > 4294967295) { http_response_code(400); exit('ID tidak valid.'); }
    $q = db()->prepare('SELECT * FROM umkm WHERE id = ?'); $q->execute([$id]);
    $row = $q->fetch();
    if (!$row) { http_response_code(404); exit('UMKM tidak ditemukan.'); }
    return $row;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post_text('action');
    $id = post_text('id');
    if ($id !== '') find_umkm($id);
    if (in_array($action, ['upload_photos', 'delete_photo', 'photo_up', 'photo_down', 'photo_cover'], true) && $id !== '') {
        $form = find_umkm($id);
        try {
            if ($action === 'upload_photos') {
                upload_umkm_photos((int) $id, $_FILES['photos'] ?? []);
                $_SESSION['flash'] = 'Foto usaha berhasil ditambahkan.';
            } elseif ($action === 'delete_photo') {
                delete_umkm_photo((int) $id, post_text('photo'));
                $_SESSION['flash'] = 'Foto usaha berhasil dihapus.';
            } else {
                move_umkm_photo((int) $id, post_text('photo'), $action);
                $_SESSION['flash'] = 'Urutan foto berhasil disimpan. Foto pertama menjadi sampul.';
            }
            redirect('admin/umkm.php?edit=' . $id . '#umkm-photos');
        } catch (RuntimeException | InvalidArgumentException $error) {
            $photoErrors[] = $error->getMessage();
            http_response_code(422);
        }
    } elseif ($action === 'save') {
        [$form, $errors] = validate_umkm($_POST);
        if (!$errors) {
            $values = [$form['name'], $form['category'], $form['description'], $form['address'], $form['wa_phone'], $form['is_sample']];
            if ($id === '') $q = $pdo->prepare('INSERT INTO umkm (name, category, description, address, wa_phone, is_sample) VALUES (?, ?, ?, ?, ?, ?)');
            else { $q = $pdo->prepare('UPDATE umkm SET name = ?, category = ?, description = ?, address = ?, wa_phone = ?, is_sample = ? WHERE id = ?'); $values[] = $id; }
            $q->execute($values);
            $_SESSION['flash'] = 'Data UMKM berhasil disimpan.';
            redirect('admin/umkm.php');
        }
        http_response_code(422);
    } elseif ($action === 'delete' && $id !== '') {
        $photos = umkm_photos((int) $id);
        $q = $pdo->prepare('DELETE FROM umkm WHERE id = ?'); $q->execute([$id]);
        foreach ($photos as $photo) {
            $path = umkm_photo_path((int) $id, $photo);
            if (!unlink($path)) error_log('Gagal membersihkan foto UMKM: ' . $path);
        }
        $_SESSION['flash'] = 'Data UMKM berhasil dihapus.';
        redirect('admin/umkm.php');
    } else { http_response_code(400); exit('Aksi tidak valid.'); }
} elseif (isset($_GET['edit']) || isset($_GET['delete'])) {
    $value = $_GET['edit'] ?? $_GET['delete'];
    if (!is_string($value)) { http_response_code(400); exit('ID tidak valid.'); }
    $row = find_umkm($value);
    if (isset($_GET['edit'])) { $id = $value; $form = $row; }
    else $delete = $row;
}
$businesses = umkm_list();
admin_header('Kelola UMKM');
?>
<div class="page-title"><div><p class="eyebrow">LAYANAN EKONOMI</p><h1>Kelola UMKM Warga</h1></div><div class="actions"><a class="button" id="umkm-add" href="?tambah=1#umkm-form">+ Tambah UMKM</a><a class="button secondary" href="<?= e(url('?layanan=ekonomi&direktori=umkm')) ?>">Lihat direktori</a><a href="<?= e(url('admin/?layanan=ekonomi')) ?>">Kembali ke layanan</a></div></div>
<?php if (isset($_SESSION['flash'])): ?><div class="notice" role="status"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif ?>
<?php if ($delete): ?><section class="panel danger"><h2>Hapus <?= e($delete['name']) ?>?</h2><p>Data usaha ini akan dihapus dari direktori.</p><form method="post" class="actions"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($delete['id']) ?>"><button class="button destructive">Ya, hapus UMKM</button><a class="button secondary" href="umkm.php">Batal</a></form></section><?php endif ?>
<section class="panel"><div class="section-title"><h2>Daftar UMKM</h2><span><?= count($businesses) ?> usaha</span></div>
<div class="table-scroll"><table><thead><tr><th>Foto usaha</th><th>Nama usaha</th><th>Kategori</th><th>WhatsApp</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($businesses as $business): ?><?php $gallery = umkm_gallery($business); ?><tr><td><?php if ($gallery): ?><a href="?edit=<?= e($business['id']) ?>#umkm-photos"><img class="umkm-admin-cover" src="<?= e($gallery[0]['src']) ?>" alt="<?= e($gallery[0]['alt']) ?>" width="96" height="72" loading="lazy"></a><small><?= count($gallery) ?> foto</small><?php else: ?><span>Belum ada foto</span><?php endif ?></td><td><?= e($business['name']) ?> <?php if ($business['is_sample']): ?><span class="badge inactive">Data contoh</span><?php endif ?></td><td><?= e($business['category']) ?></td><td><?= e($business['wa_phone'] ?: 'Belum diisi') ?></td><td><div class="actions"><a class="button secondary" href="?edit=<?= e($business['id']) ?>#umkm-photos">Kelola foto</a><a class="button secondary" href="?edit=<?= e($business['id']) ?>&amp;form=1#umkm-form">Edit</a><a class="button destructive" href="?delete=<?= e($business['id']) ?>">Hapus</a></div></td></tr><?php endforeach ?>
<?php if (!$businesses): ?><tr><td colspan="5">Belum ada data UMKM.</td></tr><?php endif ?>
</tbody></table></div></section>
<?php if ($id !== ''): ?>
<?php $gallery = umkm_gallery(['id' => $id] + $form); ?>
<section class="panel umkm-photo-panel" id="umkm-photos">
<div class="section-title"><div><h2>Foto <?= e($form['name']) ?></h2><p class="umkm-photo-help">Foto pertama menjadi sampul. Atur urutan atau hapus foto melalui tombol pada setiap kartu.</p></div><span class="umkm-photo-count"><?= count($gallery) ?> / 6 foto</span></div>
<?php if ($photoErrors): ?><div class="notice error" role="alert"><?= e(implode(' ', $photoErrors)) ?></div><?php endif ?>

<form method="post" enctype="multipart/form-data" action="umkm.php?edit=<?= e($id) ?>#umkm-photos" class="umkm-photo-upload">
<?= csrf_field() ?><input type="hidden" name="action" value="upload_photos"><input type="hidden" name="id" value="<?= e($id) ?>">
<label>Tambahkan foto<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required <?= count($gallery) >= 6 ? 'disabled' : '' ?>><small><?= count($gallery) >= 6 ? 'Galeri penuh. Hapus satu foto sebelum menambahkan foto baru.' : 'Pilih beberapa foto. JPG, PNG, WebP; maksimal 5 MB dan 8 megapiksel per foto.' ?></small></label>
<button type="submit" class="button" <?= count($gallery) >= 6 ? 'disabled' : '' ?>>Unggah foto</button>
</form>
<div class="umkm-admin-photo-grid">
<?php foreach ($gallery as $index => $preview): ?>
<div class="umkm-photo-preview"><a href="<?= e($preview['src']) ?>"><img src="<?= e($preview['src']) ?>" alt="<?= e($preview['alt']) ?>" width="320" height="240" loading="lazy"></a>
<h3><?= $index === 0 ? 'Foto sampul' : 'Foto ' . ($index + 1) ?></h3>
<form method="post" action="umkm.php?edit=<?= e($id) ?>#umkm-photos" class="umkm-photo-actions"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="photo" value="<?= e($preview['file']) ?>">
<button type="submit" name="action" value="photo_up" class="button secondary" <?= $index === 0 ? 'disabled' : '' ?>>Naik</button>
<button type="submit" name="action" value="photo_down" class="button secondary" <?= $index === count($gallery) - 1 ? 'disabled' : '' ?>>Turun</button>
<button type="submit" name="action" value="photo_cover" class="button secondary" <?= $index === 0 ? 'disabled' : '' ?>><?= $index === 0 ? 'Sampul aktif' : 'Jadikan sampul' ?></button>
<button type="submit" name="action" value="delete_photo" class="button destructive">Hapus foto</button></form>
</div>
<?php endforeach ?>
</div>
<?php if (!$gallery): ?><p class="umkm-photo-help">Belum ada foto usaha.</p><?php endif ?>
</section>
<?php endif ?>
<dialog class="service-dialog umkm-dialog" id="umkm-form" aria-labelledby="umkm-dialog-title" data-auto-open="<?= $errors || isset($_GET['tambah']) || (($_GET['form'] ?? '') === '1' && $id !== '') ? '1' : '0' ?>">
<div class="section-title"><h2 id="umkm-dialog-title"><?= $id === '' ? 'Tambah UMKM' : 'Edit UMKM' ?></h2><button class="button secondary" type="button" data-close-umkm>Tutup</button></div>
<?php if ($errors): ?><div class="notice error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form method="post" action="umkm.php#umkm-form" class="stack">
<?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($id) ?>">
<div class="form-grid"><label>Nama usaha<input name="name" maxlength="120" required autofocus value="<?= e($form['name']) ?>"></label><label>Kategori usaha<input name="category" maxlength="80" required value="<?= e($form['category']) ?>" placeholder="Contoh: Kuliner"></label></div>
<label>Deskripsi usaha<textarea name="description" maxlength="2000" rows="3" required><?= e($form['description']) ?></textarea></label>
<label>Alamat usaha<input name="address" maxlength="255" required value="<?= e($form['address']) ?>"></label>
<label>Nomor WhatsApp<input name="wa_phone" type="tel" inputmode="tel" maxlength="40" value="<?= e($form['wa_phone']) ?>" placeholder="081234567890"><small>Opsional. Nomor yang diisi akan tampil di direktori publik.</small></label>
<label class="checkbox"><input type="checkbox" name="is_sample" value="1" <?= $form['is_sample'] ? 'checked' : '' ?>> Tandai sebagai data contoh</label>
<div class="actions umkm-dialog-actions"><button class="button secondary" type="button" data-close-umkm>Batal</button><button class="button" type="submit">Simpan UMKM</button></div>
</form></dialog>
<?php admin_footer(); ?>
