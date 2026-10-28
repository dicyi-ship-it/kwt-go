<?php
$letterTypes = letter_types();
$letterIcons = ['umum' => 'books', 'domisili' => 'home', 'sktm' => 'people', 'usaha' => 'shop', 'nikah' => 'people', 'kelahiran' => 'baby', 'kematian' => 'care', 'lainnya' => 'chat'];
$letterType = $_GET['surat'] ?? '';
if (!is_string($letterType) || !isset($letterTypes[$letterType])) $letterType = '';
$selectedRt = $_GET['rt'] ?? '';
if (!is_string($selectedRt) || !preg_match('/^rt[1-6]$/D', $selectedRt)) $selectedRt = '';
$lettersPath = '?layanan=administrasi&persuratan=1';
?>
<section class="services letters-workspace" aria-labelledby="letters-title">
  <div class="section-heading"><h2 id="letters-title"><?= $letterType === '' ? 'Pilih layanan surat' : ($selectedRt === '' ? 'Pilih RT tujuan' : 'Periksa sebelum mengirim') ?></h2><a href="<?= e(url('?layanan=administrasi')) ?>">Kembali ke Administrasi</a></div>
  <ol class="letter-steps" aria-label="Tahapan persuratan">
    <li <?= $letterType === '' ? 'aria-current="step"' : '' ?>><span>1</span> Layanan surat</li>
    <li <?= $letterType !== '' && $selectedRt === '' ? 'aria-current="step"' : '' ?>><span>2</span> Pilih RT</li>
    <li <?= $letterType !== '' && $selectedRt !== '' ? 'aria-current="step"' : '' ?>><span>3</span> WhatsApp</li>
  </ol>
  <?php if ($letterType === ''): ?>
  <p class="organization-note">Pilih keperluan Anda. Pengurus RT akan membantu menjelaskan persyaratan dan proses pengurusannya.</p>
  <div class="services-grid">
    <?php foreach ($letterTypes as $key => $label): ?>
    <a class="service-card" href="<?= e(url($lettersPath . '&surat=' . $key)) ?>"><span class="service-icon" aria-hidden="true"><?= service_icon($letterIcons[$key]) ?></span><span><?= e($label) ?></span></a>
    <?php endforeach ?>
  </div>
  <?php elseif ($selectedRt === ''): ?>
  <p class="organization-note"><strong><?= e($letterTypes[$letterType]) ?></strong> &middot; Pilih RT sesuai tempat tinggal Anda.</p>
  <div class="rt-grid">
    <?php for ($rt = 1; $rt <= 6; $rt++): $key = 'rt' . $rt; $contact = $structureContacts[$key] ?? []; ?>
    <div class="letter-panel"><h3>RT <?= $rt ?></h3><p><?= e(($contact['name'] ?? '') ?: 'Nama ketua belum diisi') ?></p>
    <?php if (normalize_wa_phone($contact['wa_phone'] ?? '') !== null): ?>
    <a class="letter-button" href="<?= e(url($lettersPath . '&surat=' . $letterType . '&rt=' . $key)) ?>">Pilih RT <?= $rt ?> &rarr;</a>
    <?php else: ?><p class="organization-note">Kontak belum tersedia. Silakan hubungi pengurus lingkungan.</p><?php endif ?>
    </div>
    <?php endfor ?>
  </div>
  <p><a href="<?= e(url($lettersPath)) ?>">Ganti layanan surat</a></p>
  <?php else: $contact = $structureContacts[$selectedRt] ?? []; $phone = normalize_wa_phone($contact['wa_phone'] ?? ''); ?>
  <div class="letter-panel letter-confirmation">
    <div class="letter-recipient">
      <span class="letter-caption">RINGKASAN PENGAJUAN</span>
      <span class="letter-summary-icon" aria-hidden="true"><?= service_icon($letterIcons[$letterType]) ?></span>
      <h3><?= e($letterTypes[$letterType]) ?></h3>
      <dl><dt>Ditujukan kepada</dt><dd>Ketua RT <?= e(substr($selectedRt, 2)) ?></dd>
      <?php if (($contact['name'] ?? '') !== ''): ?><dt>Nama pengurus</dt><dd><?= e($contact['name']) ?></dd><?php endif ?>
      <?php if ($phone !== null): ?><dt>WhatsApp</dt><dd>+<?= e($phone) ?></dd><?php endif ?></dl>
      <div class="letter-change-links"><a href="<?= e(url($lettersPath . '&surat=' . $letterType)) ?>">Ganti RT</a><a href="<?= e(url($lettersPath)) ?>">Ganti layanan surat</a></div>
    </div>
    <div class="letter-preview">
      <?php if ($phone !== null): $message = letter_message($letterType, $selectedRt); ?>
      <h3>Pesan untuk Pak RT</h3>
      <p class="organization-note">Pesan berikut sudah disiapkan untuk Anda.</p>
      <blockquote class="letter-message"><?= e($message) ?></blockquote>
      <a class="letter-button" href="<?= e('https://wa.me/' . $phone . '?text=' . rawurlencode($message)) ?>" target="_blank" rel="noopener noreferrer">Buka WhatsApp Pak RT <span aria-hidden="true">&nearr;</span></a>
      <p class="letter-help">Periksa pesan, lalu tekan <strong>Kirim</strong> di WhatsApp. Pengajuan tidak disimpan di website.</p>
      <?php else: ?><p role="status">Kontak belum tersedia. Silakan hubungi pengurus lingkungan.</p><?php endif ?>
    </div>
  </div>
  <?php endif ?>
</section>
