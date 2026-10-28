<?php
declare(strict_types=1);

function structure_labels(): array
{
    $labels = ['rw12' => 'RW 12'];
    for ($rt = 1; $rt <= 6; $rt++) $labels['rt' . $rt] = 'RT ' . $rt;
    return $labels;
}

function structure_contacts(): array
{
    try {
        return db()->query('SELECT * FROM structure_contacts')->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    } catch (PDOException $error) {
        if ($error->getCode() !== '42S02') throw $error;
        return [];
    }
}

function save_structure_contact(string $key, string $name, string $phone): void
{
    $pdo = db();
    if ($name === '' && $phone === '') {
        $q = $pdo->prepare('DELETE FROM structure_contacts WHERE structure_key = ?');
        $q->execute([$key]);
        return;
    }
    $q = $pdo->prepare($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
        ? 'INSERT INTO structure_contacts (structure_key, name, wa_phone) VALUES (?, ?, ?) ON CONFLICT(structure_key) DO UPDATE SET name = excluded.name, wa_phone = excluded.wa_phone'
        : 'INSERT INTO structure_contacts (structure_key, name, wa_phone) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), wa_phone = VALUES(wa_phone)');
    $q->execute([$key, $name, $phone]);
}

function structure_contact_card(string $key, array $contacts): void
{
    $phone = $contacts[$key]['wa_phone'] ?? '';
    $label = structure_labels()[$key];
    ?><div class="structure-contact"><span>WhatsApp <?= e($label) ?></span>
    <?php if ($phone !== ''): ?>
    <a href="<?= e('https://wa.me/' . $phone) ?>" aria-label="<?= e('Hubungi ' . $label . ' melalui WhatsApp: +' . $phone) ?>">+<?= e($phone) ?> <span aria-hidden="true">&nearr;</span></a>
    <?php else: ?><small>Nomor belum diisi</small><?php endif ?>
    </div><?php
}
