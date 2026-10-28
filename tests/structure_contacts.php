<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/services.php';
require __DIR__ . '/../app/structure-contacts.php';
$pdo = db();
$pdo->beginTransaction();
try {
    save_structure_contact('rt1', 'Pak RT Uji', normalize_wa_phone('081234567890'));
    $contacts = structure_contacts();
    if (($contacts['rt1']['wa_phone'] ?? '') !== '6281234567890' || $contacts['rt1']['name'] !== 'Pak RT Uji') throw new RuntimeException('Phone save failed');
    ob_start(); structure_contact_card('rt1', $contacts); $html = ob_get_clean();
    if (substr_count($html, 'href=') !== 1 || !str_contains($html, 'https://wa.me/6281234567890')) throw new RuntimeException('Contact link failed');
    ob_start(); structure_contact_card('rw12', []); $html = ob_get_clean();
    if (str_contains($html, 'href=') || !str_contains($html, 'Nomor belum diisi')) throw new RuntimeException('Empty contact failed');
    if (count(structure_labels()) !== 7) throw new RuntimeException('Expected seven structures');
    save_structure_contact('rt1', 'Pak RT Baru', '6281234567891');
    if (structure_contacts()['rt1']['name'] !== 'Pak RT Baru') throw new RuntimeException('Update failed');
    save_structure_contact('rt1', '', '');
    if (isset(structure_contacts()['rt1'])) throw new RuntimeException('Delete failed');
    echo "PASS: save/read contact, one WhatsApp link, empty state, seven structures\n";
} finally { $pdo->rollBack(); }
