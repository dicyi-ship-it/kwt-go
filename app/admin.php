<?php
declare(strict_types=1);
require_once __DIR__ . '/services.php';
require_once __DIR__ . '/icons.php';

function require_admin(): void
{
    admin_session();
    if (!isset($_SESSION['admin_id'])) redirect('login.php');
}

function admin_header(string $title): void
{
    ?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> · Villa Ilhami Blok Mina</title><link rel="stylesheet" href="<?= e(url('admin.css?v=' . filemtime(dirname(__DIR__) . '/public/admin.css'))) ?>"></head>
<body><div class="admin-shell"><header class="admin-header"><a class="brand" href="<?= e(url('admin/')) ?>"><?= service_icon('plant') ?> Villa Ilhami Blok Mina <span>Admin</span></a><nav aria-label="Navigasi pengelola"><a href="<?= e(url()) ?>">Lihat website ↗</a><?php if (isset($_SESSION['admin_id'])): ?><a href="<?= e(url('admin/')) ?>" <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' && empty($_GET['layanan']) ? 'aria-current="page"' : '' ?>>Dashboard</a><?php foreach (service_channels() as $key => $item): ?><a href="<?= e(url('admin/?layanan=' . $key)) ?>" <?= (($_GET['layanan'] ?? '') === $key || ($key === 'administrasi' && basename($_SERVER['SCRIPT_NAME']) === 'structure.php') || ($key === 'ekonomi' && basename($_SERVER['SCRIPT_NAME']) === 'umkm.php')) ? 'aria-current="page"' : '' ?>><?= e($item['title']) ?></a><?php endforeach ?><form method="post" action="<?= e(url('admin/')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="button secondary" type="submit">Keluar</button></form><?php endif ?></nav></header><main>
<?php
}

function admin_footer(): void
{
    echo '</main><footer class="admin-footer">Villa Ilhami Blok Mina · Jl. Mina Raya, Panunggangan Bar., Kec. Cibodas, Kota Tangerang, Banten 15138</footer></div><script src="' . e(url('admin.js?v=' . filemtime(dirname(__DIR__) . '/public/admin.js'))) . '" defer></script></body></html>';
}

function validate_service(): array
{
    $data = ['label' => post_text('label'), 'icon' => post_text('icon'), 'target_type' => post_text('target_type'), 'wa_phone' => post_text('wa_phone'), 'wa_message' => is_string($_POST['wa_message'] ?? null) ? $_POST['wa_message'] : '', 'url' => post_text('url'), 'sort_order' => post_text('sort_order'), 'is_active' => post_text('is_active') === '1' ? 1 : 0];
    $errors = [];
    if ($data['label'] === '' || mb_strlen($data['label']) > 120) $errors[] = 'Nama tombol wajib diisi, maksimal 120 karakter.';
    if (!isset(service_icons()[$data['icon']])) $errors[] = 'Pilih salah satu gambar ikon yang tersedia.';
    if ($data['target_type'] === 'whatsapp') {
        $phone = normalize_wa_phone($data['wa_phone']);
        if ($phone === null) $errors[] = 'Isi nomor Indonesia yang benar, misalnya 081234567890 atau +62 812 3456 7890. Jangan masukkan link.';
        else $data['wa_phone'] = $phone;
        if (mb_strlen($data['wa_message']) > 2000 || str_contains($data['wa_message'], "\0")) $errors[] = 'Pesan pembuka maksimal 2000 karakter dan tidak boleh mengandung karakter null.';
        $data['url'] = null;
    } elseif ($data['target_type'] === 'link') {
        $u = parse_url($data['url']);
        if (strlen($data['url']) > 4096 || preg_match('/[\x00-\x1f\x7f]/', $data['url'])
        || !filter_var(str_replace(' ', '%20', $data['url']), FILTER_VALIDATE_URL)
        || !is_array($u) || !in_array($u['scheme'] ?? '', ['http', 'https'], true)
        || isset($u['user']) || isset($u['pass'])) {
            $errors[] = 'Alamat website harus berupa link http:// atau https:// yang valid, maksimal 4096 byte.';
        }
        $data['wa_phone'] = null;
        $data['wa_message'] = null;
    } else {
        $errors[] = 'Pilih tujuan tombol: WhatsApp atau website/aplikasi.';
    }
    if (!preg_match('/^\d{1,7}$/D', $data['sort_order']) || (int) $data['sort_order'] > 1000000) $errors[] = 'Urutan harus berupa angka 0 sampai 1000000.';
    return [$data, $errors];
}
