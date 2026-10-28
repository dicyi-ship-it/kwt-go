<?php
declare(strict_types=1);
// Keep old bookmarks and submitted forms on the combined administration page.
$_GET['layanan'] = 'administrasi';
if ($_SERVER['REQUEST_METHOD'] === 'POST') $_POST['action'] = 'save_structure';
require __DIR__ . '/index.php';
