<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';

$username = $argv[1] ?? '';
$reset = ($argv[2] ?? '') === '--reset';
$sql = ($argv[2] ?? '') === '--sql';
if (!preg_match('/^[a-zA-Z0-9_.-]{3,100}$/D', $username)) {
    fwrite(STDERR, "Pemakaian: php scripts/create-admin.php USERNAME [--reset|--sql]\nKirim password melalui stdin, bukan argumen command.\n");
    exit(1);
}
$password = rtrim(fgets(STDIN, 1024) ?: '', "\r\n");
if (strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
    fwrite(STDERR, "Password wajib 12–72 byte tanpa null.\n");
    exit(1);
}
$hash = password_hash($password, PASSWORD_DEFAULT);
if ($sql) {
    // Both values are restricted/generated locally, never raw SQL from HTTP input.
    fwrite(STDOUT, "INSERT INTO admins (username, password_hash) VALUES ('" . $username . "', '" . $hash . "');\n");
    exit;
}
$pdo = db();
$q = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
$q->execute([$username]);
$id = $q->fetchColumn();
if ($id && !$reset) { fwrite(STDERR, "Username sudah ada. Gunakan --reset untuk mengganti password.\n"); exit(1); }
if (!$id && $reset) { fwrite(STDERR, "Username belum ada; jalankan tanpa --reset.\n"); exit(1); }
$q = $pdo->prepare($reset ? 'UPDATE admins SET password_hash = ? WHERE id = ?' : 'INSERT INTO admins (password_hash, username) VALUES (?, ?)');
$q->execute([$hash, $reset ? $id : $username]);
fwrite(STDOUT, $reset ? "Password admin diperbarui.\n" : "Admin berhasil dibuat.\n");
