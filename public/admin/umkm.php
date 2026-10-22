<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require dirname(__DIR__, 2) . '/app/admin.php';
require dirname(__DIR__, 2) . '/app/umkm.php';
web_headers();
require_admin();
$pdo = db();
$errors = [];
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
    if ($action === 'save') {
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
        $q = $pdo->prepare('DELETE FROM umkm WHERE id = ?'); $q->execute([$id]);
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
<div class="page-title"><div><p class="eyebrow">LAYANAN EKONOMI</p><h1>Kelola UMKM Warga</h1></div><div class="actions"><a class="button secondary" href="<?= e(url('?layanan=ekonomi&direktori=umkm')) ?>">Lihat direktori</a><a href="<?= e(url('admin/')) ?>">Dashboard</a></div></div>
<?php if (isset($_SESSION['flash'])): ?><div class="notice" role="status"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif ?>
<?php if ($delete): ?><section class="panel danger"><h2>Hapus <?= e($delete['name']) ?>?</h2><p>Data usaha ini akan dihapus dari direktori.</p><form method="post" class="actions"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($delete['id']) ?>"><button class="button destructive">Ya, hapus UMKM</button><a class="button secondary" href="umkm.php">Batal</a></form></section><?php endif ?>
<section class="panel"><div class="section-title"><h2>Daftar UMKM</h2><span><?= count($businesses) ?> usaha</span></div>
<div class="table-scroll"><table><thead><tr><th>Nama usaha</th><th>Kategori</th><th>WhatsApp</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($businesses as $business): ?><tr><td><?= e($business['name']) ?> <?php if ($business['is_sample']): ?><span class="badge inactive">Data contoh</span><?php endif ?></td><td><?= e($business['category']) ?></td><td><?= e($business['wa_phone'] ?: 'Belum diisi') ?></td><td><div class="actions"><a href="?edit=<?= e($business['id']) ?>#umkm-form">Edit</a><a class="danger-link" href="?delete=<?= e($business['id']) ?>">Hapus</a></div></td></tr><?php endforeach ?>
<?php if (!$businesses): ?><tr><td colspan="4">Belum ada data UMKM.</td></tr><?php endif ?>
</tbody></table></div></section>
<section class="panel" id="umkm-form"><h2><?= $id === '' ? 'Tambah UMKM' : 'Edit UMKM' ?></h2>
<?php if ($errors): ?><div class="notice error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form method="post" action="umkm.php#umkm-form" class="stack">
<?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($id) ?>">
<div class="form-grid"><label>Nama usaha<input name="name" maxlength="120" required value="<?= e($form['name']) ?>"></label><label>Kategori usaha<input name="category" maxlength="80" required value="<?= e($form['category']) ?>" placeholder="Contoh: Kuliner"></label></div>
<label>Deskripsi usaha<textarea name="description" maxlength="2000" rows="3" required><?= e($form['description']) ?></textarea></label>
<label>Alamat usaha<input name="address" maxlength="255" required value="<?= e($form['address']) ?>"></label>
<label>Nomor WhatsApp<input name="wa_phone" type="tel" inputmode="tel" maxlength="40" value="<?= e($form['wa_phone']) ?>" placeholder="081234567890"><small>Opsional. Nomor yang diisi akan tampil di direktori publik.</small></label>
<label class="checkbox"><input type="checkbox" name="is_sample" value="1" <?= $form['is_sample'] ? 'checked' : '' ?>> Tandai sebagai data contoh</label>
<div class="actions"><button class="button" type="submit">Simpan UMKM</button><a class="button secondary" href="umkm.php">Reset form</a></div>
</form></section>
<?php admin_footer(); ?>
