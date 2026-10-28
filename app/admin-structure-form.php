

<p>Kelola nama ketua dan nomor WhatsApp RW 12 serta RT 1–6. Data tampil di website warga. Kosongkan kedua kolom untuk menghapus data pengurus pada jabatan tersebut.</p>
<?php if ($structureErrors): ?><div class="notice error" role="alert"><?php foreach ($structureErrors as $error): ?><p><?= e($error) ?></p><?php endforeach ?></div><?php endif ?>
<form method="post" class="stack" action="<?= e(url('admin/?layanan=administrasi#pengurus')) ?>">
<?= csrf_field() ?><input type="hidden" name="action" value="save_structure">
<?php foreach (structure_labels() as $key => $label): ?>
<fieldset class="destination-fields"><legend><?= e($label) ?></legend>
<label>Nama Ketua <?= e($label) ?><input name="<?= e($key) ?>_name" maxlength="120" value="<?= e($contacts[$key]['name'] ?? '') ?>" placeholder="Nama ketua"></label>
<label>WhatsApp <?= e($label) ?><input type="tel" inputmode="tel" name="<?= e($key) ?>" maxlength="40" value="<?= e($contacts[$key]['wa_phone'] ?? '') ?>" placeholder="Contoh: 081234567890"></label>
</fieldset>
<?php endforeach ?>
<div class="actions"><button class="button" type="submit">Simpan data pengurus</button></div>
</form>
