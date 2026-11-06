<?php
declare(strict_types=1);
$folders = ['dapur-mina-rasa','kreasi-jahit-ilhami','kebun-hijau-mina','bersih-wangi-laundry','kriya-anyam-panbar'];
$hashes = [];
foreach ($folders as $folder) {
    for ($number = 1; $number <= 5; $number++) {
        $path = dirname(__DIR__) . '/public/assets/umkm-samples/' . $folder . '/' . $number . '.jpg';
        $info = getimagesize($path);
        if (!$info || $info[2] !== IMAGETYPE_JPEG || $info[0] > 1200) throw new RuntimeException('Illustration format or dimensions invalid.');
        $hashes[] = hash_file('sha256', $path);
    }
}
if (count(array_unique($hashes)) !== 25) throw new RuntimeException('Expected 25 distinct illustrations.');
echo "PASS: 25 distinct JPEG illustrations with optimized dimensions.\n";
