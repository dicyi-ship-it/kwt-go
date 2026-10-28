<?php
declare(strict_types=1);

set_exception_handler(static function (Throwable $error): void {
    error_log('Villa Ilhami Blok Mina: ' . $error->getMessage());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Villa Ilhami Blok Mina gagal dijalankan; periksa konfigurasi dan database.\n");
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><title>Villa Ilhami Blok Mina</title><h1>Layanan belum tersedia</h1><p>Periksa konfigurasi dan koneksi database. Detail tersedia di log server.</p></html>';
});

function config(): array
{
    static $config;
    if ($config === null) {
        $path = getenv('MINA_CONFIG') ?: dirname(__DIR__) . '/config.local.php';
        if (!is_file($path)) {
            throw new RuntimeException('Buat config.local.php dari config.example.php sebelum menjalankan aplikasi.');
        }
        $config = require $path;
        if (!is_array($config) || !preg_match('/^[a-f0-9]{64}$/D', $config['app_key'] ?? '')) {
            throw new RuntimeException('app_key harus berisi 64 karakter hex acak.');
        }
        $url = $config['base_url'] ?? '';
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
            || parse_url($url, PHP_URL_QUERY) !== null || parse_url($url, PHP_URL_FRAGMENT) !== null
            || parse_url($url, PHP_URL_USER) !== null) {
            throw new RuntimeException('base_url tidak valid.');
        }
        $config['base_url'] = rtrim($url, '/');
        $config['timezone'] = $config['timezone'] ?? 'Asia/Jakarta';
        date_default_timezone_set($config['timezone']);
    }
    return $config;
}

function db(): PDO
{
    static $pdo;
    if ($pdo === null) {
        $c = config()['db'];
        if (($c['driver'] ?? 'mysql') === 'sqlite') {
            $path = realpath($c['path'] ?? '');
            $public = realpath(dirname(__DIR__) . '/public');
            if ($path === false || !is_file($path) || str_starts_with(strtolower(str_replace('\\', '/', $path)), strtolower(str_replace('\\', '/', $public)) . '/')) {
                throw new RuntimeException('File SQLite harus tersedia di luar folder public.');
            }
            $pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            return $pdo;
        }
        foreach (['name', 'host'] as $key) {
            if (!preg_match('/^[a-zA-Z0-9_.-]+$/D', $c[$key] ?? '')) {
                throw new RuntimeException('Nama database atau host tidak valid.');
            }
        }
        $transport = !empty($c['socket']) ? 'unix_socket=' . $c['socket'] : 'host=' . $c['host'] . ';port=' . (int) $c['port'];
        $pdo = new PDO('mysql:' . $transport . ';dbname=' . $c['name'] . ';charset=utf8mb4', $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return config()['base_url'] . '/' . ltrim($path, '/');
}

function cookie_path(): string
{
    return rtrim(parse_url(config()['base_url'], PHP_URL_PATH) ?? '', '/') . '/';
}

function secure_cookie(): bool
{
    return parse_url(config()['base_url'], PHP_URL_SCHEME) === 'https';
}

function web_headers(): void
{
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
}

function admin_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('mina_admin');
        session_start([
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'cookie_httponly' => true,
            'cookie_secure' => secure_cookie(),
            'cookie_samesite' => 'Lax',
            'cookie_path' => cookie_path(),
        ]);
    }
    $now = time();
    if (isset($_SESSION['admin_id']) && ($now - ($_SESSION['last_active'] ?? 0) > 1800 || $now - ($_SESSION['login_at'] ?? 0) > 28800)) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    if (isset($_SESSION['admin_id'])) {
        $_SESSION['last_active'] = $now;
    }
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? null;
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Form kedaluwarsa atau tidak valid. Muat ulang halaman.');
    }
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function post_text(string $name): string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) ? trim($value) : '';
}
