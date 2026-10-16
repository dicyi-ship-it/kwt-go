<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/visitors.php';

web_headers();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}
if (known_bot()) { http_response_code(204); exit; }
if (isset($_COOKIE['mina_admin'])) {
    admin_session();
    $admin = isset($_SESSION['admin_id']);
    session_write_close();
    if ($admin) { http_response_code(204); exit; }
}
$id = visitor_id();
$proof = post_text('proof');
$valid = preg_match('/^(\d{10})\.([a-f0-9]{64})$/D', $proof, $m);
if (!$id || !$valid || (int) $m[1] < time() || (int) $m[1] > time() + 1800 || !hash_equals(visitor_proof($id, (int) $m[1]), $proof)) {
    http_response_code(403);
    exit;
}
record_visit($id);
http_response_code(204);
