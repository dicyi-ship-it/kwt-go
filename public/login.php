<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/admin.php';
web_headers();
admin_session();
if (isset($_SESSION['admin_id'])) redirect('admin/');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = post_text('username');
    $password = $_POST['password'] ?? '';
    $bucket = hash_hmac('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . intdiv(time(), 900), config()['app_key']);
    $pdo = db();
    $pdo->exec('DELETE FROM login_attempts WHERE expires_at < CURRENT_TIMESTAMP');
    // Reserve the attempt atomically, including concurrent requests.
    $q = $pdo->prepare($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
        ? 'INSERT INTO login_attempts (bucket_hash, attempts, expires_at) VALUES (?, 1, ?) ON CONFLICT(bucket_hash) DO UPDATE SET attempts = attempts + 1'
        : 'INSERT INTO login_attempts (bucket_hash, attempts, expires_at) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE attempts = attempts + 1');
    $q->execute([$bucket, gmdate('Y-m-d H:i:s', time() + 1800)]);
    $q = $pdo->prepare('SELECT attempts FROM login_attempts WHERE bucket_hash = ?');
    $q->execute([$bucket]);
    if ((int) $q->fetchColumn() > 5) {
        http_response_code(429);
        header('Retry-After: ' . (900 - time() % 900));
        $error = 'Terlalu banyak percobaan. Coba kembali setelah 15 menit.';
    } else {
        $q = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ?');
        $q->execute([mb_substr($username, 0, 100)]);
        $admin = $q->fetch();
        // A real hash also prevents skipping password work for unknown accounts.
        $hash = $admin['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
        $verified = is_string($password) && strlen($password) <= 72 && password_verify($password, $hash);
        if (!$admin || !$verified || $username !== $admin['username']) {
            $error = 'Username atau password salah.';
        } else {
            session_regenerate_id(true);
            $_SESSION = ['admin_id' => (int) $admin['id'], 'username' => $admin['username'], 'login_at' => time(), 'last_active' => time()];
            $q = $pdo->prepare('DELETE FROM login_attempts WHERE bucket_hash = ?');
            $q->execute([$bucket]);
            redirect('admin/');
        }
    }
}
admin_header('Login admin');
?>
<section class="panel login-panel"><p class="eyebrow">VILLA ILHAMI BLOK MINA</p><h1>Login Pengelola</h1><p class="muted">Kelola data pengurus RW dan RT, daftar UMKM, serta layanan warga dalam satu tempat.</p>
<?php if ($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif ?>
<form method="post" class="stack">
<?= csrf_field() ?>
<label>Username<input name="username" autocomplete="username" required maxlength="100" value="<?= e(post_text('username')) ?>"></label>
<label>Password<input name="password" type="password" autocomplete="current-password" required maxlength="72"></label>
<button class="button" type="submit">Masuk</button>
</form></section>
<?php admin_footer(); ?>
