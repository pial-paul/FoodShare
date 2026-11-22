<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

// Each transaction takes the same donation lock as an interactive handoff.
// Bounded batches avoid holding locks across a whole day's expired inventory.
$cutoff = today();
$total = 0;
do {
    $items = rows("SELECT id FROM donations WHERE status IN ('available','requested') AND expiry_date<? ORDER BY id LIMIT 100", [$cutoff]);
    foreach ($items as $item) {
        db()->begin_transaction();
        try {
            $changed = rows("SELECT id FROM donations WHERE id=? AND status IN ('available','requested')
                AND expiry_date<? FOR UPDATE", [$item['id'], $cutoff]);
            if ($changed) {
                db()->execute_query("UPDATE requests SET status='declined' WHERE donation_id=? AND status IN ('pending','approved')", [$item['id']]);
                db()->execute_query("UPDATE donations SET status='expired' WHERE id=?", [$item['id']]);
                $total++;
            }
            db()->commit();
        } catch (Throwable $e) {
            db()->rollback();
            throw $e;
        }
    }
} while (count($items) === 100);
echo "Expired $total listings.\n";
