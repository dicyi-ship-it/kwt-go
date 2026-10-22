<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/visitors.php';
require dirname(__DIR__) . '/app/services.php';
require dirname(__DIR__) . '/app/icons.php';
require dirname(__DIR__) . '/app/structure-contacts.php';
$structureContacts = structure_contacts();
web_headers();
$services = db()->query('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
$channels = [
    'administrasi' => ['title' => 'Layanan Administrasi', 'icon' => 'home', 'description' => 'Kenali pengurus RW dan RT, serta akses informasi administrasi warga.', 'detail' => 'Struktur RW 12 & RT 1-6'],
    'ekonomi' => ['title' => 'Layanan Ekonomi', 'icon' => 'shop', 'description' => 'Temukan layanan usaha dan kegiatan ekonomi di lingkungan kita.', 'detail' => 'UMKM & kegiatan warga'],
    'permasyarakatan' => ['title' => 'Layanan Permasyarakatan', 'icon' => 'people', 'description' => 'Akses layanan sosial, kesehatan, keamanan, dan lingkungan.', 'detail' => 'Sosial & lingkungan'],
];
$channel = $_GET['layanan'] ?? '';
if (!is_string($channel) || !isset($channels[$channel])) $channel = '';
$channelServices = array_filter($services, static function (array $service) use ($channel): bool {
    $group = match ($service['icon']) {
        'shop', 'plant' => 'ekonomi',
        'home', 'megaphone', 'chat', 'phone' => 'administrasi',
        default => 'permasyarakatan',
    };
    return $group === $channel;
});
require dirname(__DIR__) . '/app/umkm.php';
$showUmkm = $channel === 'ekonomi' && ($_GET['direktori'] ?? '') === 'umkm';
$businesses = $showUmkm ? umkm_list() : [];
$visitProof = prepare_visit();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <?php if ($visitProof !== null): ?><meta name="visit-proof" content="<?= e($visitProof) ?>" data-endpoint="<?= e(url('track.php')) ?>"><?php endif ?>
  <meta name="theme-color" content="#185b36">
  <meta name="description" content="Layanan warga Perumahan Villa Ilhami Blok Mina. Jl. Mina Raya, Panunggangan Bar., Kec. Cibodas, Kota Tangerang, Banten 15138.">
  <title>Villa Ilhami Blok Mina — Layanan Warga</title>
  <link rel="stylesheet" href="assets/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="page-shell">
    <header class="site-header">
      <a href="<?= e(url()) ?>" class="brand" aria-label="Villa Ilhami Blok Mina, halaman utama">
        <span class="brand-mark" aria-hidden="true"><?= service_icon('plant') ?></span>
        <span><strong>Villa Ilhami</strong><small>Blok Mina · Kota Tangerang</small></span>
      </a>
      <div class="share-button-wrapper" data-target="#share_modal">
        <button type="button" class="share-trigger" aria-label="Bagikan halaman" aria-haspopup="dialog" aria-controls="share_modal">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a3 3 0 1 0-2.8-4L8.7 7.8a3 3 0 1 0 0 4.4l6.5 3.8a3 3 0 1 0 1-1.7l-6.5-3.8a3 3 0 0 0 0-1l6.5-3.8A3 3 0 0 0 18 8Z"/></svg>
          <span>Bagikan</span>
        </button>
      </div>
    </header>
    <main id="main-content">
      <?php if ($channel === ''): ?>
      <section class="welcome" aria-labelledby="page-title">
        <p class="eyebrow">PORTAL WARGA &middot; RW 12</p>
        <h1 id="page-title">Satu lingkungan,<br>semua saling terhubung.</h1>
        <p class="welcome-description">Selamat datang di layanan warga Villa Ilhami Blok Mina.<br>Pilih layanan yang Anda butuhkan untuk memulai.</p>
        <p class="welcome-address">Kelurahan Panunggangan Barat &middot; Kota Tangerang</p>
        <div class="welcome-badge">6 RT <span>di dalam satu RW</span></div>
      </section>
      <section class="services" aria-labelledby="services-title">
        <div class="section-heading"><h2 id="services-title">Ada yang bisa kami bantu?</h2><span>3 kanal layanan warga</span></div>
        <div class="channel-grid">
          <?php foreach ($channels as $key => $item): ?>
          <a class="channel-card channel-<?= e($key) ?>" href="<?= e(url('?layanan=' . $key)) ?>">
            <span class="channel-icon" aria-hidden="true"><?= service_icon($item['icon']) ?></span>
            <h3><?= e($item['title']) ?></h3>
            <p><?= e($item['description']) ?></p>
            <span class="channel-detail"><?= e($item['detail']) ?></span>
            <span class="channel-action">Buka layanan <span aria-hidden="true">&nearr;</span></span>
          </a>
          <?php endforeach ?>
        </div>
      </section>
      <?php else: ?>
      <nav class="breadcrumb-nav" aria-label="Jejak navigasi"><a href="<?= e(url()) ?>">Beranda</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($channels[$channel]['title']) ?></span></nav>
      <section class="welcome channel-welcome" aria-labelledby="page-title">
        <p class="eyebrow">RW 12 &middot; KELURAHAN PANUNGGANGAN BARAT</p>
        <h1 id="page-title"><?= $showUmkm ? 'Direktori UMKM Warga' : e($channels[$channel]['title']) ?></h1>
        <p class="welcome-description"><?= e($channels[$channel]['description']) ?></p>
      </section>
      <nav class="channel-nav" aria-label="Pilihan layanan">
        <?php foreach ($channels as $key => $item): ?>
        <a href="<?= e(url('?layanan=' . $key)) ?>" <?= $channel === $key ? 'aria-current="page"' : '' ?>><?= e($item['title']) ?></a>
        <?php endforeach ?>
      </nav>
      <?php if ($channel === 'administrasi'): ?>
      <section class="organization" aria-labelledby="organization-title">
        <div class="section-heading"><div><p class="section-kicker">PENGURUS LINGKUNGAN</p><h2 id="organization-title">Struktur Pengurus RW 12</h2></div><span>Kelurahan Panunggangan Barat</span></div>
        <p class="organization-note">Nama pengurus belum tersedia. Susunan jabatan berikut merupakan kerangka awal dan akan disesuaikan dengan data resmi.</p>
        <div class="rw-chart" aria-label="Kerangka struktur RW 12">
          <div class="officer officer-lead"><span class="officer-avatar" aria-hidden="true"><?= service_icon('home') ?></span><h3>Ketua RW 12</h3><p>Belum diisi</p></div>
          <div class="officer-branches">
            <?php foreach (['Sekretaris RW', 'Bendahara RW'] as $role): ?>
            <div class="officer"><h3><?= e($role) ?></h3><p>Belum diisi</p></div>
            <?php endforeach ?>
          </div>
          <?php structure_contact_card('rw12', $structureContacts); ?>
        </div>
        <div class="section-heading rt-heading"><h2>Struktur Pengurus RT</h2><span>RT 1 hingga RT 6</span></div>
        <div class="rt-grid">
          <?php for ($rt = 1; $rt <= 6; $rt++): ?>
          <details class="rt-card">
            <summary><span class="rt-number"><?= sprintf('%02d', $rt) ?></span><span><strong>RT <?= $rt ?></strong><small>RW 12 &middot; Panunggangan Barat</small></span><span class="rt-expand" aria-hidden="true">+</span></summary>
            <div class="rt-officers">
              <?php foreach (['Ketua RT', 'Sekretaris RT', 'Bendahara RT'] as $role): ?>
              <div><h3><?= e($role) ?> <?= $rt ?></h3><p>Belum diisi</p></div>
              <?php endforeach ?>
            </div>
            <?php structure_contact_card('rt' . $rt, $structureContacts); ?>
          </details>
          <?php endfor ?>
        </div>
      </section>
      <?php endif ?>
      <?php if ($showUmkm): ?>
      <section class="services" aria-labelledby="umkm-title">
        <div class="section-heading"><h2 id="umkm-title">Usaha dari lingkungan kita</h2><span><?= count($businesses) ?> UMKM</span></div>
        <p class="organization-note">Kenali produk dan jasa warga. Usaha bertanda &ldquo;Data contoh&rdquo; merupakan dataset demonstrasi, bukan usaha yang telah terverifikasi.</p>
        <div class="umkm-grid">
          <?php foreach ($businesses as $business): ?>
          <article class="umkm-card">
            <div class="umkm-card-top"><span class="channel-icon" aria-hidden="true"><?= service_icon('shop') ?></span><?php if ($business['is_sample']): ?><span class="umkm-sample">Data contoh</span><?php endif ?></div>
            <span class="umkm-category"><?= e($business['category']) ?></span>
            <h3><?= e($business['name']) ?></h3>
            <p class="umkm-description"><?= e($business['description']) ?></p>
            <p class="umkm-address"><strong>Alamat usaha</strong><?= e($business['address']) ?></p>
            <?php if ($business['wa_phone'] !== ''): ?><a class="umkm-contact" href="<?= e('https://wa.me/' . $business['wa_phone']) ?>">Hubungi via WhatsApp <span aria-hidden="true">&nearr;</span></a>
            <?php else: ?><span class="umkm-contact umkm-contact-empty">WhatsApp belum tersedia</span><?php endif ?>
          </article>
          <?php endforeach ?>
        </div>
        <?php if (!$businesses): ?><p class="organization-note">Belum ada UMKM yang terdaftar.</p><?php endif ?>
      </section>
      <?php else: ?>
      <section class="services" aria-labelledby="services-title">
        <div class="section-heading"><h2 id="services-title">Akses Layanan</h2><span><?= count($channelServices) ?> layanan</span></div>
        <div class="services-grid">
          <?php foreach ($channelServices as $service): ?>
          <a class="service-card" href="<?= e(mb_strtoupper(trim($service['label'])) === 'UMKM' ? url('?layanan=ekonomi&direktori=umkm') : service_url($service)) ?>"><span class="service-icon" aria-hidden="true"><?= service_icon($service['icon']) ?></span><span><?= e($service['label']) ?></span></a>
          <?php endforeach ?>
        </div>
        <?php if (!$channelServices): ?><p class="organization-note">Belum ada layanan yang aktif di kanal ini.</p><?php endif ?>
      </section>
      <?php endif ?>
      <?php endif ?>
    </main>
    <footer class="site-footer">
      <nav id="top_left_floating" class="scroll-navigation" aria-label="Navigasi halaman">
        <button id="scrollUp" type="button" onclick="scrollToTop()">↑ <span>Ke atas</span></button>
        <button id="scrollDown" type="button" onclick="scrollToBottom()">↓ <span>Ke bawah</span></button>
      </nav>
      <div class="footer-program">
        <div class="footer-emblem" aria-hidden="true">
          <svg viewBox="0 0 48 48" fill="none"><path d="m5 18 19-9 19 9-19 9L5 18Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M13 22v12c7 6 15 6 22 0V22M43 18v14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <div class="footer-program-copy">
          <span class="footer-kicker">PENDIDIKAN, TEKNOLOGI &amp; PEMBERDAYAAN</span>
          <h2>Teknologi untuk Masyarakat.</h2>
          <p>Dikembangkan melalui Program Pengabdian kepada Masyarakat<br><strong>Sekolah Tinggi Teknologi Informasi NIIT I-Tech</strong></p>
        </div>
        <span class="footer-program-tag">Pengabdian kepada Masyarakat</span>
      </div>
      <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Perumahan Villa Ilhami Blok Mina<span class="footer-tagline">Layanan terpadu untuk warga yang saling terhubung.</span></p>
        <a class="footer-admin" href="<?= e(url('login.php')) ?>">Login Pengelola <span aria-hidden="true">&nearr;</span></a>
      </div>
    </footer>
  </div>
<div class="modal fade" id="share_modal" tabindex="-1" role="dialog" aria-labelledby="share-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="modal-title" id="share-title">
                            <svg class="svg-inline--fa fa-share-nodes fa-fw fa-sm mr-2 text-dark" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="share-nodes" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M352 224c53 0 96-43 96-96s-43-96-96-96s-96 43-96 96c0 4 .2 8 .7 11.9l-94.1 47C145.4 170.2 121.9 160 96 160c-53 0-96 43-96 96s43 96 96 96c25.9 0 49.4-10.2 66.6-26.9l94.1 47c-.5 3.9-.7 7.8-.7 11.9c0 53 43 96 96 96s96-43 96-96s-43-96-96-96c-25.9 0-49.4 10.2-66.6 26.9l-94.1-47c.5-3.9 .7-7.8 .7-11.9s-.2-8-.7-11.9l94.1-47C302.6 213.8 326.1 224 352 224z"></path></svg>
                            Berbagi                        </h5>
                        <button type="button" class="close" data-dismiss="modal" title="Tutup">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>



                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <a href="mailto:?body=<?= e(rawurlencode(url())) ?>" target="_blank" class="btn btn-gray-100 rounded-2x" style="color: #3b5998; background-color: rgba(59, 89, 152, 0.1);" data-toggle="tooltip" title="" aria-label="Bagikan melalui Email" data-original-title="Bagikan melalui Email">
    <svg class="svg-inline--fa fa-envelope fa-fw" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="envelope" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48L48 64zM0 176L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-208L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"></path></svg>
</a>


<button type="button" class="btn btn-gray-100 rounded-2x d-none" style="color: #3a18f2; background-color: rgba(58, 24, 242, 0.1);" data-toggle="tooltip" title="" data-native-share="" data-share-url="<?= e(url()) ?>" data-original-title="Bagikan melalui Perangkat" aria-label="Bagikan melalui Perangkat">
    <svg class="svg-inline--fa fa-share fa-fw" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="share" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M307 34.8c-11.5 5.1-19 16.6-19 29.2l0 64-112 0C78.8 128 0 206.8 0 304C0 417.3 81.5 467.9 100.2 478.1c2.5 1.4 5.3 1.9 8.1 1.9c10.9 0 19.7-8.9 19.7-19.7c0-7.5-4.3-14.4-9.8-19.5C108.8 431.9 96 414.4 96 384c0-53 43-96 96-96l96 0 0 64c0 12.6 7.4 24.1 19 29.2s25 3 34.4-5.4l160-144c6.7-6.1 10.6-14.7 10.6-23.8s-3.8-17.7-10.6-23.8l-160-144c-9.4-8.5-22.9-10.6-34.4-5.4z"></path></svg>
</button>

<a href="https://www.facebook.com/sharer/sharer.php?u=<?= e(rawurlencode(url())) ?>" target="_blank" rel="noreferrer" class="btn btn-gray-100 rounded-2x" style="color: #1877F2; background-color: rgba(24, 119, 242, 0.1);" data-toggle="tooltip" title="" aria-label="Bagikan melalui Facebook" data-original-title="Bagikan melalui Facebook">
    <svg class="svg-inline--fa fa-facebook fa-fw" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="facebook" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M512 256C512 114.6 397.4 0 256 0S0 114.6 0 256C0 376 82.7 476.8 194.2 504.5V334.2H141.4V256h52.8V222.3c0-87.1 39.4-127.5 125-127.5c16.2 0 44.2 3.2 55.7 6.4V172c-6-.6-16.5-1-29.6-1c-42 0-58.2 15.9-58.2 57.2V256h83.6l-14.4 78.2H287V510.1C413.8 494.8 512 386.9 512 256h0z"></path></svg>
</a>

<a href="https://x.com/share?url=<?= e(rawurlencode(url())) ?>" target="_blank" rel="noreferrer" class="btn btn-gray-100 rounded-2x" style="color: #1DA1F2; background-color: rgba(29, 161, 242, 0.1);" data-toggle="tooltip" title="" aria-label="Bagikan melalui X" data-original-title="Bagikan melalui X">
    <svg class="svg-inline--fa fa-twitter fa-fw" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="twitter" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.583 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 20.791-32.161 39.308-52.628 54.253z"></path></svg>
</a>

<a href="https://linkedin.com/shareArticle?url=<?= e(rawurlencode(url())) ?>" target="_blank" rel="noreferrer" class="btn btn-gray-100 rounded-2x" style="color: #0077B5; background-color: rgba(0, 119, 181, 0.1);" data-toggle="tooltip" title="" aria-label="Bagikan melalui LinkedIn" data-original-title="Bagikan melalui LinkedIn">
    <svg class="svg-inline--fa fa-linkedin fa-fw" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="linkedin" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M416 32H31.9C14.3 32 0 46.5 0 64.3v383.4C0 465.5 14.3 480 31.9 480H416c17.6 0 32-14.5 32-32.3V64.3c0-17.8-14.4-32.3-32-32.3zM135.4 416H69V202.2h66.5V416zm-33.2-243c-21.3 0-38.5-17.3-38.5-38.5S80.9 96 102.2 96c21.2 0 38.5 17.3 38.5 38.5 0 21.3-17.2 38.5-38.5 38.5zm282.1 243h-66.4V312c0-24.8-.5-56.7-34.5-56.7-34.6 0-39.9 27-39.9 54.9V416h-66.4V202.2h63.7v29.2h.9c8.9-16.8 30.6-34.5 62.9-34.5 67.2 0 79.7 44.3 79.7 101.9V416z"></path></svg>
</a>

<a href="https://wa.me/?text=<?= e(rawurlencode(url())) ?>" target="_blank" rel="noreferrer" class="btn btn-gray-100 rounded-2x" style="color: #25D366; background-color: rgba(37, 211, 102, 0.1);" data-toggle="tooltip" title="" aria-label="Bagikan melalui WhatsApp" data-original-title="Bagikan melalui WhatsApp">
    <svg class="svg-inline--fa fa-whatsapp fa-fw" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="whatsapp" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"></path></svg>
</a>

<a href="https://t.me/share/url?url=<?= e(rawurlencode(url())) ?>" target="_blank" rel="noreferrer" class="btn btn-gray-100 rounded-2x" style="color: #0088cc; background-color: rgba(0, 136, 204, 0.1);" data-toggle="tooltip" title="" aria-label="Bagikan melalui Telegram" data-original-title="Bagikan melalui Telegram">
    <svg class="svg-inline--fa fa-telegram fa-fw" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="telegram" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512" data-fa-i2svg=""><path fill="currentColor" d="M248,8C111.033,8,0,119.033,0,256S111.033,504,248,504,496,392.967,496,256,384.967,8,248,8ZM362.952,176.66c-3.732,39.215-19.881,134.378-28.1,178.3-3.476,18.584-10.322,24.816-16.948,25.425-14.4,1.326-25.338-9.517-39.287-18.661-21.827-14.308-34.158-23.215-55.346-37.177-24.485-16.135-8.612-25,5.342-39.5,3.652-3.793,67.107-61.51,68.335-66.746.153-.655.3-3.1-1.154-4.384s-3.59-.849-5.135-.5q-3.283.746-104.608,69.142-14.845,10.194-26.894,9.934c-8.855-.191-25.888-5.006-38.551-9.123-15.531-5.048-27.875-7.717-26.8-16.291q.84-6.7,18.45-13.7,108.446-47.248,144.628-62.3c68.872-28.647,83.183-33.623,92.511-33.789,2.052-.034,6.639.474,9.61,2.885a10.452,10.452,0,0,1,3.53,6.716A43.765,43.765,0,0,1,362.952,176.66Z"></path></svg>
</a>
                    </div>

                    <div class="form-group mt-4">
                        <div class="input-group">
                            <input id="share" type="text" class="form-control" value="<?= e(url()) ?>" onclick="this.select();" readonly="readonly" style="">

                            <div class="input-group-append">
                                <button type="button" class="btn btn-light border border-left-0" data-toggle="tooltip" title="" aria-label="Salin ke papan klip" data-copy="Salin ke papan klip" data-copied="Disalin!" data-clipboard-target="#share" data-original-title="Salin ke papan klip">
                                    <svg class="svg-inline--fa fa-copy fa-fw fa-sm" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M208 0L332.1 0c12.7 0 24.9 5.1 33.9 14.1l67.9 67.9c9 9 14.1 21.2 14.1 33.9L448 336c0 26.5-21.5 48-48 48l-192 0c-26.5 0-48-21.5-48-48l0-288c0-26.5 21.5-48 48-48zM48 128l80 0 0 64-64 0 0 256 192 0 0-32 64 0 0 48c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 176c0-26.5 21.5-48 48-48z"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  <script src="app.js"></script>
  <script src="visitors.js"></script>
</body>
</html>
