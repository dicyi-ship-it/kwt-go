<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require dirname(__DIR__, 2) . '/app/admin.php';
require dirname(__DIR__, 2) . '/app/visitors.php';
web_headers();
require_admin();
$pdo = db();
$errors = [];
$form = ['id' => '', 'label' => '', 'icon' => 'home', 'target_type' => 'whatsapp', 'wa_phone' => '', 'wa_message' => '', 'url' => '', 'sort_order' => (string) ((int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM services')->fetchColumn()), 'is_active' => 1];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post_text('action');
    if ($action === 'logout') {
        $_SESSION = [];
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => cookie_path(), 'secure' => secure_cookie(), 'httponly' => true, 'samesite' => 'Lax']);
        session_destroy();
        redirect('login.php');
    }
    $id = post_text('id');
    if ($id !== '' && (!ctype_digit($id) || (int) $id < 1 || (int) $id > 4294967295)) { http_response_code(400); exit('ID tidak valid.'); }
    if ($action === 'save') {
        [$data, $errors] = validate_service();
        $form = ['id' => $id] + $data;
        if (!$errors) {
            $values = [$data['label'], $data['icon'], $data['target_type'], $data['wa_phone'], $data['wa_message'], $data['url'], (int) $data['sort_order'], $data['is_active']];
            if ($id !== '') {
                $q = $pdo->prepare('SELECT id FROM services WHERE id = ?');
                $q->execute([$id]);
                if (!$q->fetch()) { http_response_code(404); exit('Tombol tidak ditemukan.'); }
                $q = $pdo->prepare('UPDATE services SET label = ?, icon = ?, target_type = ?, wa_phone = ?, wa_message = ?, url = ?, sort_order = ?, is_active = ? WHERE id = ?');
                $q->execute([...$values, $id]);
            } else {
                $q = $pdo->prepare('INSERT INTO services (label, icon, target_type, wa_phone, wa_message, url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $q->execute($values);
            }
            $_SESSION['flash'] = 'Tombol berhasil disimpan.';
            redirect('admin/');
        }
        http_response_code(422);
    } elseif ($action === 'delete' && $id !== '') {
        $q = $pdo->prepare('DELETE FROM services WHERE id = ?');
        $q->execute([$id]);
        if ($q->rowCount() === 0) { http_response_code(404); exit('Tombol tidak ditemukan.'); }
        $_SESSION['flash'] = 'Tombol berhasil dihapus.';
        redirect('admin/');
    } else {
        http_response_code(400); exit('Aksi tidak valid.');
    }
}
$delete = null;
foreach (['edit', 'delete'] as $mode) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET[$mode])) continue;
    $id = $_GET[$mode];
    if (!is_string($id) || !ctype_digit($id) || (int) $id < 1 || (int) $id > 4294967295) { http_response_code(400); exit('ID tidak valid.'); }
    $q = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $q->execute([$id]);
    $row = $q->fetch();
    if (!$row) { http_response_code(404); exit('Tombol tidak ditemukan.'); }
    if ($mode === 'edit') $form = $row;
    else $delete = $row;
}
$stats = visitor_stats();
$services = $pdo->query('SELECT * FROM services ORDER BY sort_order, id')->fetchAll();
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
admin_header('Dashboard');
?>
<div class="page-title"><div><p class="eyebrow">DASHBOARD</p><h1>Halo, <?= e($_SESSION['username']) ?></h1><p class="muted">Statistik dan layanan warga dalam satu tempat.</p></div><a class="button" href="<?= e(url('admin/')) ?>#service-form">+ Tambah tombol</a></div>
<?php if ($flash): ?><div class="notice" role="status"><?= e($flash) ?></div><?php endif ?>
<p><a class="button secondary" href="<?= e(url('admin/structure.php')) ?>">Kelola WhatsApp RW dan RT</a></p>
<p><a class="button secondary" href="<?= e(url('admin/umkm.php')) ?>">Kelola Data UMKM</a></p>
<section aria-label="Statistik pengunjung" class="stats-grid">
<?php foreach (['today' => 'Pengunjung hari ini', 'month' => 'Pengunjung bulan ini', 'total' => 'Total pengunjung'] as $key => $label): ?>
<article class="panel stat"><span><?= e($label) ?></span><strong><?= number_format($stats[$key], 0, ',', '.') ?></strong><small><?= e($key === 'today' ? date('d/m/Y') : ($key === 'month' ? date('m/Y') : 'Sejak pencatatan dimulai')) ?></small></article>
<?php endforeach ?>
</section>
<p class="stats-note">Pengunjung unik berdasarkan cookie, bukan jumlah refresh. Cookie yang sama dihitung sekali saja selama bulan berjalan. Zona waktu: <?= e(config()['timezone']) ?>. Admin yang sedang login dan bot yang dikenali tidak dihitung. Cookie/perangkat berbeda dapat terhitung lagi; angka ini bukan verifikasi identitas manusia.</p>
<?php if ($delete): ?><section class="panel danger" aria-labelledby="delete-title"><h2 id="delete-title">Hapus tombol “<?= e($delete['label']) ?>”?</h2><p>Tombol akan dihapus permanen dari database. Anda bisa menonaktifkannya melalui Edit bila hanya ingin menyembunyikannya.</p><form method="post" class="actions"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($delete['id']) ?>"><button type="submit" class="button destructive">Ya, hapus tombol</button><a class="button secondary" href="<?= e(url('admin/')) ?>">Batal</a></form></section><?php endif ?>
<section class="panel"><div class="section-title"><h2>Semua tombol</h2><span><?= count($services) ?> tombol</span></div>
<div class="table-scroll"><table><thead><tr><th>Urutan</th><th>Layanan</th><th>Tujuan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($services as $service): ?><tr><td><?= e($service['sort_order']) ?></td><td><span aria-hidden="true"><?= service_icon($service['icon']) ?></span> <?= e($service['label']) ?></td><td><?php if ($service['target_type'] === 'whatsapp'): ?><span class="destination-summary">WhatsApp · <?= e($service['wa_phone']) ?></span><small class="message-summary"><?= e($service['wa_message'] ?: 'Tanpa pesan pembuka') ?></small><?php else: ?><span class="destination-summary">Website / aplikasi</span><?php endif ?><a class="test-target" href="<?= e(service_url($service)) ?>" target="_blank" rel="noopener noreferrer">Coba buka ↗</a></td><td><span class="badge <?= $service['is_active'] ? '' : 'inactive' ?>"><?= $service['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td><div class="actions"><a href="?edit=<?= e($service['id']) ?>#service-form">Edit</a><a class="danger-link" href="?delete=<?= e($service['id']) ?>#delete-title">Hapus</a></div></td></tr><?php endforeach ?>
<?php if (!$services): ?><tr><td colspan="5">Belum ada tombol. Tambahkan melalui form di bawah.</td></tr><?php endif ?>
</tbody></table></div></section>
<section class="panel" id="service-form"><h2><?= $form['id'] === '' ? 'Tambah tombol' : 'Edit tombol' ?></h2>
<?php if ($errors): ?><div class="notice error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form method="post" class="stack" action="<?= e(url('admin/')) ?>#service-form">
<?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($form['id']) ?>">
<label>Nama tombol<input name="label" required maxlength="120" value="<?= e($form['label']) ?>" placeholder="Contoh: Info Warga"></label>
<fieldset class="icon-fieldset"><legend>Pilih gambar tombol</legend><p class="muted">Klik salah satu gambar. Tidak perlu mengetik emoji.</p><div class="icon-picker">
<?php foreach (service_icons() as $key => $icon): ?><label class="icon-choice"><input type="radio" name="icon" value="<?= e($key) ?>" <?= $form['icon'] === $key ? 'checked' : '' ?> required><span><?= service_icon($key) ?><span><?= e($icon[0]) ?></span></span></label><?php endforeach ?>
</div></fieldset>
<label>Tombol ini membuka apa?<select name="target_type" id="target-type"><option value="whatsapp" <?= $form['target_type'] === 'whatsapp' ? 'selected' : '' ?>>Chat WhatsApp</option><option value="link" <?= $form['target_type'] === 'link' ? 'selected' : '' ?>>Website / aplikasi lain</option></select></label>
<fieldset class="destination-fields" data-destination="whatsapp"><legend>Kontak WhatsApp</legend>
<label>Nomor WhatsApp<input name="wa_phone" type="tel" inputmode="tel" maxlength="40" value="<?= e($form['wa_phone']) ?>" placeholder="Contoh: 081234567890" autocomplete="tel"><small>Boleh diawali 08, 62, atau +62. Link WhatsApp dibuat otomatis.</small></label>
<label>Pesan pembuka (pretext)<textarea name="wa_message" rows="4" maxlength="2000" placeholder="Assalamualaikum, saya ingin meminta bantuan pelayanan... "><?= e($form['wa_message']) ?></textarea><small>Pesan ini muncul saat warga membuka chat. Warga tetap memilih sendiri untuk mengirimnya.</small></label>
</fieldset>
<fieldset class="destination-fields" data-destination="link"><legend>Website / aplikasi</legend><label>Alamat website<input name="url" maxlength="4096" value="<?= e($form['url']) ?>" placeholder="Tempel alamat website di sini" inputmode="url"><small>Khusus tujuan selain WhatsApp, misalnya aplikasi Tangerang Live.</small></label></fieldset>
<div class="form-grid"><label>Urutan tampil<input name="sort_order" type="number" min="0" max="1000000" required value="<?= e($form['sort_order']) ?>"><small>Isi 1, 2, 3, dan seterusnya. Angka terkecil muncul lebih dahulu.</small></label><label class="checkbox"><input name="is_active" type="checkbox" value="1" <?= $form['is_active'] ? 'checked' : '' ?>> Tampilkan di website</label></div>
<div class="actions"><button class="button" type="submit">Simpan tombol</button><a class="button secondary" href="<?= e(url('admin/')) ?>">Reset form</a></div>
</form></section>
<?php admin_footer(); ?>
