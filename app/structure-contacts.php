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
        return db()->query('SELECT structure_key, wa_phone FROM structure_contacts')->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $error) {
        if ($error->getCode() !== '42S02') throw $error;
        return [];
    }
}

function structure_contact_card(string $key, array $contacts): void
{
    $phone = $contacts[$key] ?? '';
    $label = structure_labels()[$key];
    ?><div class="structure-contact"><span>WhatsApp <?= e($label) ?></span>
    <?php if ($phone !== ''): ?>
    <a href="<?= e('https://wa.me/' . $phone) ?>" aria-label="<?= e('Hubungi ' . $label . ' melalui WhatsApp: +' . $phone) ?>">+<?= e($phone) ?> <span aria-hidden="true">&nearr;</span></a>
    <?php else: ?><small>Nomor belum diisi</small><?php endif ?>
    </div><?php
}
