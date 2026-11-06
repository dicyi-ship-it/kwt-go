<?php
$gallery = umkm_gallery($businessDetail);
?>
<section class="business-detail" aria-labelledby="business-name">
  <a class="business-back" href="<?= e(url('?layanan=ekonomi&direktori=umkm')) ?>">&larr; Kembali ke daftar usaha</a>
  <div class="business-layout">
    <section class="business-gallery" aria-labelledby="gallery-title">
      <div class="business-gallery-heading"><h2 id="gallery-title">Foto usaha dan produk</h2><span><?= count($gallery) ?> foto</span></div>
      <?php if (!$gallery): ?><div class="business-photo-empty"><p>Belum ada foto usaha.</p></div><?php else: ?>
      <div class="business-photos">
        <?php foreach ($gallery as $index => $photo): ?>
        <a href="<?= e($photo['src']) ?>" aria-label="Perbesar <?= e($photo['alt']) ?>"><img src="<?= e($photo['src']) ?>" width="960" height="720" alt="<?= e($photo['alt']) ?>" <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>></a>
        <?php endforeach ?>
      </div>
      <p class="business-photo-note">Pilih foto untuk melihat ukuran penuh.<?= array_filter($gallery, static fn(array $photo): bool => $photo['sample']) ? ' Galeri ini memuat gambar ilustrasi untuk contoh tampilan, bukan foto usaha asli.' : '' ?></p>
      <?php endif ?>
    </section>
    <div class="business-information">
      <span class="umkm-category"><?= e($businessDetail['category']) ?></span>
      <h1 id="business-name"><?= e($businessDetail['name']) ?></h1>
      <p class="business-location">Villa Ilhami Blok Mina &middot; RW 12</p>
      <section class="business-about" aria-labelledby="about-title"><h2 id="about-title">Tentang usaha</h2><p><?= e($businessDetail['description']) ?></p></section>
      <dl class="business-facts">
        <div><dt>Alamat usaha</dt><dd><?= e($businessDetail['address']) ?></dd></div>
        <div><dt>Kontak WhatsApp</dt><dd><?= e($businessDetail['wa_phone'] !== '' ? '+' . $businessDetail['wa_phone'] : 'Belum tersedia') ?></dd></div>
      </dl>
      <?php if ($businessDetail['wa_phone'] !== ''): ?>
      <a class="business-contact" href="<?= e('https://wa.me/' . $businessDetail['wa_phone']) ?>">Hubungi <?= e($businessDetail['name']) ?></a>
      <p class="business-contact-note">Tanyakan produk, harga, dan ketersediaan langsung melalui WhatsApp.</p>
      <?php endif ?>
    </div>
  </div>
</section>
