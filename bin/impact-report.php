<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

$month = $argv[1] ?? '';
$from = DateTimeImmutable::createFromFormat('!Y-m', $month, new DateTimeZone('UTC'));
if (!$from || $from->format('Y-m') !== $month) {
    fwrite(STDERR, "Usage: php bin/impact-report.php YYYY-MM\n");
    exit(2);
}
$period = [$from->format('Y-m-d'), $from->modify('+1 month')->format('Y-m-d')];
$row = rows("SELECT COUNT(*) AS confirmed_pickups,COUNT(received_kg) AS weighed_pickups,
    SUM(received_kg) AS reported_kg, COUNT(DISTINCT receiver_id) AS receiving_accounts
    FROM requests WHERE status='completed' AND completed_at>=? AND completed_at<?", $period)[0];
echo json_encode(['month_utc' => $month] + $row, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
