<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

$engines = rows("SELECT table_name,engine FROM information_schema.tables
    WHERE table_schema=DATABASE() AND table_name IN ('users','donations','requests')");
$bad = count($engines) !== 3 || array_filter($engines, fn($row) => $row['engine'] !== 'InnoDB');
$conflicts = rows("SELECT d.id FROM donations d LEFT JOIN requests r ON r.donation_id=d.id
    GROUP BY d.id,d.status HAVING d.status IS NULL
    OR SUM(CASE WHEN r.status IN ('pending','approved','completed') THEN 1 ELSE 0 END) <>
        CASE WHEN d.status IN ('requested','picked_up') THEN 1 ELSE 0 END
    OR SUM(CASE WHEN r.status='completed' THEN 1 ELSE 0 END) <>
        CASE WHEN d.status='picked_up' THEN 1 ELSE 0 END");
if ($bad || $conflicts) {
    fwrite(STDERR, "Upgrade blocked. Check all three InnoDB tables and reconcile these donation IDs: "
        . implode(', ', array_column($conflicts,'id')) . PHP_EOL);
    exit(1);
}
echo "Baseline engines and reservation states passed preflight.\n";
