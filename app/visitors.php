<?php
declare(strict_types=1);

function known_bot(): bool
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return $ua === '' || (bool) preg_match('/bot|crawler|spider|slurp|preview|facebookexternalhit|headless|curl|wget|python|monitor|uptime/i', $ua);
}

function visitor_id(): ?string
{
    $cookie = $_COOKIE['mina_visitor'] ?? '';
    if (!is_string($cookie) || !preg_match('/^([a-f0-9]{64})\.([a-f0-9]{64})$/D', $cookie, $m)) {
        return null;
    }
    return hash_equals(hash_hmac('sha256', $m[1], config()['app_key']), $m[2]) ? $m[1] : null;
}

function visitor_proof(string $id, int $expires): string
{
    return $expires . '.' . hash_hmac('sha256', 'visit|' . $id . '|' . $expires, config()['app_key']);
}

function prepare_visit(): ?string
{
    if (known_bot()) {
        return null;
    }
    if (isset($_COOKIE['mina_admin'])) {
        admin_session();
        $admin = isset($_SESSION['admin_id']);
        session_write_close();
        if ($admin) return null;
    }
    $id = visitor_id();
    if ($id === null) {
        $id = bin2hex(random_bytes(32));
        setcookie('mina_visitor', $id . '.' . hash_hmac('sha256', $id, config()['app_key']), [
            'expires' => time() + 31536000,
            'path' => cookie_path(),
            'secure' => secure_cookie(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    return visitor_proof($id, time() + 1800);
}

function record_visit(string $id): void
{
    $hash = hash('sha256', $id);
    $now = gmdate('Y-m-d H:i:s');
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? 'INSERT INTO visitors (visitor_hash, first_seen, last_seen) VALUES (?, ?, ?) ON CONFLICT(visitor_hash) DO UPDATE SET last_seen = MAX(last_seen, excluded.last_seen)'
            : 'INSERT INTO visitors (visitor_hash, first_seen, last_seen) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE last_seen = GREATEST(last_seen, VALUES(last_seen))');
        $q->execute([$hash, $now, $now]);
        $q = $pdo->prepare($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? 'INSERT INTO visitor_days (visitor_hash, visit_date) VALUES (?, ?) ON CONFLICT(visitor_hash, visit_date) DO NOTHING'
            : 'INSERT IGNORE INTO visitor_days (visitor_hash, visit_date) VALUES (?, ?)');
        $q->execute([$hash, date('Y-m-d')]);
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function visitor_stats(): array
{
    $pdo = db();
    $q = $pdo->prepare('SELECT COUNT(*) FROM visitor_days WHERE visit_date = ?');
    $q->execute([date('Y-m-d')]);
    $today = (int) $q->fetchColumn();
    $q = $pdo->prepare('SELECT COUNT(DISTINCT visitor_hash) FROM visitor_days WHERE visit_date >= ? AND visit_date < ?');
    $q->execute([date('Y-m-01'), date('Y-m-01', strtotime('first day of next month'))]);
    return ['today' => $today, 'month' => (int) $q->fetchColumn(), 'total' => (int) $pdo->query('SELECT COUNT(*) FROM visitors')->fetchColumn()];
}
