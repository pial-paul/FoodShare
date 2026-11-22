<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

function changeHandoff(int $actorId, int $donationId, string $action, int $requestId = 0, string $weight = ''): void
{
    $db = db();
    $db->begin_transaction();
    try {
        $item = rows('SELECT donor_id,status,expiry_date FROM donations WHERE id=? FOR UPDATE', [$donationId])[0] ?? null;
        $actor = rows('SELECT role FROM users WHERE id=? AND disabled_at IS NULL FOR UPDATE', [$actorId])[0] ?? null;
        if (!$actor || !$item) {
            throw new DomainException('This action is unavailable.');
        }
        $active = rows("SELECT id,receiver_id,status FROM requests WHERE donation_id=? AND assignment_slot=1 FOR UPDATE", [$donationId])[0] ?? null;
        $expired = $item['expiry_date'] < today();
        $owner = $actor['role'] === 'donor' && (int)$item['donor_id'] === $actorId;
        $admin = $actor['role'] === 'admin';
        if ($action === 'request') {
            $donor = rows('SELECT disabled_at FROM users WHERE id=? FOR UPDATE', [$item['donor_id']])[0];
            if ($actor['role'] !== 'receiver' || $active || $expired || $item['status'] !== 'available' || $donor['disabled_at'] !== null) {
                throw new DomainException('This donation is no longer available to request.');
            }
            $db->execute_query("INSERT INTO requests (receiver_id,donation_id,status) VALUES (?,?,'pending')", [$actorId, $donationId]);
            $db->execute_query("UPDATE donations SET status='requested' WHERE id=?", [$donationId]);
        } elseif ($action === 'withdraw' && ($owner || $admin) && !$active && $item['status'] === 'available') {
            $db->execute_query("UPDATE donations SET status='withdrawn' WHERE id=?", [$donationId]);
        } else {
            if (!$active || (int)$active['id'] !== $requestId || $item['status'] !== 'requested') {
                throw new DomainException('This request has already changed. Refresh your dashboard.');
            }
            $recipient = $actor['role'] === 'receiver' && (int)$active['receiver_id'] === $actorId;
            if ($action === 'approve' && $owner && !$expired && $active['status'] === 'pending') {
                $db->execute_query("UPDATE requests SET status='approved' WHERE id=?", [$requestId]);
            } elseif ($action === 'cancel' && ($owner || $recipient || $admin) && in_array($active['status'], ['pending','approved'], true)) {
                $db->execute_query("UPDATE requests SET status='declined' WHERE id=?", [$requestId]);
                $db->execute_query('UPDATE donations SET status=? WHERE id=?', [$expired ? 'expired' : 'available', $donationId]);
            } elseif ($action === 'collect' && $recipient && !$expired && $active['status'] === 'approved') {
                if ($weight !== '' && (!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $weight) || (float)$weight <= 0)) {
                    throw new DomainException('Enter the measured weight in kg to two decimal places, or leave it blank.');
                }
                $db->execute_query("UPDATE requests SET status='completed',completed_at=UTC_TIMESTAMP(),received_kg=? WHERE id=?",
                    [$weight === '' ? null : $weight, $requestId]);
                $db->execute_query("UPDATE donations SET status='picked_up' WHERE id=?", [$donationId]);
            } else {
                throw new DomainException('This action is unavailable for this account or pickup state.');
            }
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}
