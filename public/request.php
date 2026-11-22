<?php
require_once __DIR__ . '/../src/web.php';
require_once __DIR__ . '/../src/handoffs.php';
$actor = requireUser();
requirePost();
try {
    changeHandoff((int)$actor['id'], (int)field($_POST,'donation_id'), field($_POST,'action') ?: 'request',
        (int)field($_POST,'request_id'), field($_POST,'received_kg'));
    $message = 'Donation updated.';
} catch (DomainException $e) {
    $message = $e->getMessage();
}
redirectTo($actor['role'] === 'admin' ? 'admin.php?tab=donations' : 'dashboard.php', $message);
