<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require dirname(__DIR__, 2) . '/app/admin.php';
require dirname(__DIR__, 2) . '/app/structure-contacts.php';
web_headers();
require_admin();
$contacts = structure_contacts();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $contacts = [];
    foreach (structure_labels() as $key => $label) {
        $input = post_text($key);
        $phone = $input === '' ? '' : normalize_wa_phone($input);
        if ($phone === null) $errors[] = 'Nomor ' . $label . ' tidak valid. Gunakan format 08 atau +62.';
        $contacts[$key] = $phone ?? $input;
    }
    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $query = $pdo->prepare('INSERT INTO structure_contacts (structure_key, wa_phone) VALUES (?, ?) ON DUPLICATE KEY UPDATE wa_phone = VALUES(wa_phone)');
            foreach ($contacts as $key => $phone) $query->execute([$key, $phone]);
            $pdo->commit();
        } catch (Throwable $error) {
            $pdo->rollBack();
            throw $error;
        }
        $_SESSION['flash'] = 'Nomor WhatsApp pengurus berhasil disimpan.';
        redirect('admin/structure.php');
    }
    http_response_code(422);
}
admin_header('Kontak Struktur RW dan RT');
?>
<section class="panel">
<h1>Kontak WhatsApp RW dan RT</h1>
<p>Satu nomor untuk RW 12 dan satu nomor untuk masing-masing RT. Nomor yang disimpan akan tampil di website dan dapat diklik untuk membuka WhatsApp. Kosongkan jika belum tersedia.</p>
<?php if (isset($_SESSION['flash'])): ?><div class="notice" role="status"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif ?>
<?php if ($errors): ?><div class="notice error" role="alert"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach ?></div><?php endif ?>
<form method="post" class="stack">
<?= csrf_field() ?>
<?php foreach (structure_labels() as $key => $label): ?>
<label>WhatsApp <?= e($label) ?><input type="tel" inputmode="tel" name="<?= e($key) ?>" maxlength="40" value="<?= e($contacts[$key] ?? '') ?>" placeholder="Contoh: 081234567890"></label>
<?php endforeach ?>
<div class="actions"><button class="button" type="submit">Simpan nomor WhatsApp</button><a class="button secondary" href="<?= e(url('admin/')) ?>">Kembali ke dashboard</a></div>
</form></section>
<?php admin_footer(); ?>
