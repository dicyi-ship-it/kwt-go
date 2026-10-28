<?php
declare(strict_types=1);
$contacts = structure_contacts();
$structureErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post_text('action') === 'save_structure') {
    csrf_check();
    $contacts = [];
    foreach (structure_labels() as $key => $label) {
        $input = post_text($key);
        $name = post_text($key . '_name');
        if (!is_string($_POST[$key] ?? '') || !is_string($_POST[$key . '_name'] ?? '')) $structureErrors[] = 'Data ' . $label . ' tidak valid.';
        if (mb_strlen($name) > 120 || str_contains($name, "\0")) $structureErrors[] = 'Nama ketua ' . $label . ' maksimal 120 karakter dan tidak boleh mengandung karakter null.';
        $phone = $input === '' ? '' : normalize_wa_phone($input);
        if ($phone === null) $structureErrors[] = 'Nomor ' . $label . ' tidak valid. Gunakan format 08 atau +62.';
        $contacts[$key] = ['name' => $name, 'wa_phone' => $phone ?? $input];
    }
    if (!$structureErrors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            foreach ($contacts as $key => $contact) save_structure_contact($key, $contact['name'], $contact['wa_phone']);
            $pdo->commit();
        } catch (Throwable $error) {
            $pdo->rollBack();
            throw $error;
        }
        $_SESSION['flash'] = 'Data pengurus berhasil disimpan.';
        redirect('admin/?layanan=administrasi&pengurus=1#pengurus');
    }
    http_response_code(422);
}
