<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/services.php';
require __DIR__ . '/../app/structure-contacts.php';
$pdo = db();
$pdo->beginTransaction();
try {
    $q = $pdo->prepare('INSERT INTO structure_contacts (structure_key, wa_phone) VALUES (?, ?) ON DUPLICATE KEY UPDATE wa_phone = VALUES(wa_phone)');
    $q->execute(['rt1', normalize_wa_phone('081234567890')]);
    $contacts = structure_contacts();
    if (($contacts['rt1'] ?? '') !== '6281234567890') throw new RuntimeException('Phone save failed');
    ob_start(); structure_contact_card('rt1', $contacts); $html = ob_get_clean();
    if (substr_count($html, 'href=') !== 1 || !str_contains($html, 'https://wa.me/6281234567890')) throw new RuntimeException('Contact link failed');
    ob_start(); structure_contact_card('rw12', []); $html = ob_get_clean();
    if (str_contains($html, 'href=') || !str_contains($html, 'Nomor belum diisi')) throw new RuntimeException('Empty contact failed');
    if (count(structure_labels()) !== 7) throw new RuntimeException('Expected seven structures');
    echo "PASS: save/read contact, one WhatsApp link, empty state, seven structures\n";
} finally { $pdo->rollBack(); }
