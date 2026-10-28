<?php
declare(strict_types=1);

function letter_types(): array
{
    return [
        'umum' => 'Pengantar Umum',
        'domisili' => 'Keterangan Domisili',
        'sktm' => 'Surat Keterangan Tidak Mampu (SKTM)',
        'usaha' => 'Keterangan Usaha',
        'nikah' => 'Pengantar Nikah',
        'kelahiran' => 'Lapor Kelahiran',
        'kematian' => 'Lapor Kematian',
        'lainnya' => 'Administrasi Lainnya',
    ];
}

function letter_message(string $type, string $rt): string
{
    if (!isset(letter_types()[$type]) || !preg_match('/^rt[1-6]$/D', $rt)) throw new InvalidArgumentException('Pilihan surat atau RT tidak valid.');
    return 'Assalamualaikum Pak RT, saya warga RT ' . substr($rt, 2) . ' ingin mengurus ' . letter_types()[$type] . '. Mohon informasi persyaratan dan prosesnya. Terima kasih.';
}
