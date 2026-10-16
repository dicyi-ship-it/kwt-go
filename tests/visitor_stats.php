<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || getenv('MINA_TEST_ALLOW_MUTATION') !== '1') {
    exit("Gunakan hanya database pengujian dan MINA_TEST_ALLOW_MUTATION=1.\n");
}
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/visitors.php';

$pdo = db();
$before = visitor_stats();
$pdo->beginTransaction();
try {
    $visitor = $pdo->prepare('INSERT INTO visitors (visitor_hash, first_seen, last_seen) VALUES (?, ?, ?)');
    $day = $pdo->prepare('INSERT IGNORE INTO visitor_days (visitor_hash, visit_date) VALUES (?, ?)');
    $previousMonth = date('Y-m-d', strtotime('last day of previous month'));
    $firstDay = date('Y-m-01');
    $today = date('Y-m-d');
    // Visitor A: previous month only; B: current month and today; C: today only.
    foreach ([[$previousMonth], [$firstDay, $today], [$today]] as $dates) {
        $hash = hash('sha256', random_bytes(32));
        $visitor->execute([$hash, gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s')]);
        foreach ($dates as $date) $day->execute([$hash, $date]);
    }
    $after = visitor_stats();
    $expected = ['today' => $before['today'] + 2, 'month' => $before['month'] + 2, 'total' => $before['total'] + 3];
    if ($after !== $expected) throw new RuntimeException('Hitungan lintas hari/bulan salah.');
    echo "PASS: visitors across days/months, monthly deduplication, lifetime count.\n";
} finally {
    $pdo->rollBack();
}
if (visitor_stats() !== $before) throw new RuntimeException('Rollback fixture gagal.');
echo "PASS: fixture transaction rolled back.\n";
