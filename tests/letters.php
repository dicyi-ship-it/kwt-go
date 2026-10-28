<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/letters.php';
if (count(letter_types()) !== 8) throw new RuntimeException('Expected eight letter services.');
foreach (letter_types() as $key => $label) {
    foreach (['rt1', 'rt6'] as $rt) {
        $message = letter_message($key, $rt);
        if (!str_contains($message, $label) || !str_contains($message, 'warga RT ' . substr($rt, 2))) throw new RuntimeException('Wrong letter or RT in message.');
        if (rawurldecode(rawurlencode($message)) !== $message) throw new RuntimeException('Message encoding changed.');
    }
}
foreach ([['bad', 'rt1'], ['umum', 'rw12'], ['umum', 'rt7'], ['umum', 'rt1<script>']] as [$type, $rt]) {
    try { letter_message($type, $rt); } catch (InvalidArgumentException) { continue; }
    throw new RuntimeException('Invalid choice accepted.');
}
echo "PASS: letter choices, RT-specific messages, encoding, invalid choice rejection.\n";
