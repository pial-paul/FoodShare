<?php
require_once __DIR__ . '/../src/web.php';
require_once __DIR__ . '/../includes/action.php';
$actor = requireUser();
if ($actor['role'] === 'admin') {
    redirectTo('admin.php');
}
$donor = $actor['role'] === 'donor';
$page = pageNumber();
$offset = ($page - 1) * 20;
if ($donor) {
    $stats = rows("SELECT COUNT(*) total,COUNT(CASE WHEN status='available' AND expiry_date>=? THEN 1 END) available
        FROM donations WHERE donor_id=?", [today(), $actor['id']])[0];
    $requests = rows('SELECT COUNT(*) total FROM requests r JOIN donations d ON d.id=r.donation_id WHERE d.donor_id=?', [$actor['id']])[0];
    $items = rows("SELECT d.*,r.id request_id,r.status request_status,u.name partner
        FROM donations d LEFT JOIN requests r ON r.donation_id=d.id AND r.assignment_slot=1 LEFT JOIN users u ON u.id=r.receiver_id
        WHERE d.donor_id=? ORDER BY d.id DESC LIMIT 21 OFFSET $offset", [$actor['id']]);
} else {
    $stats = rows("SELECT COUNT(*) total,COUNT(CASE WHEN status='completed' AND completed_at IS NOT NULL THEN 1 END) collected
        FROM requests WHERE receiver_id=?", [$actor['id']])[0];
    $items = rows("SELECT d.*,r.id request_id,r.status request_status,u.name partner FROM requests r
        JOIN donations d ON d.id=r.donation_id JOIN users u ON u.id=d.donor_id WHERE r.receiver_id=?
        ORDER BY r.id DESC LIMIT 21 OFFSET $offset", [$actor['id']]);
}
page('Dashboard'); ?>
<p class="eyebrow"><?= h($actor['name']) ?> · <?= h(ucfirst($actor['role'])) ?></p>
<h1><?= $donor ? 'Your donations' : 'Your pickups' ?></h1>
<?php if ($donor): ?><p><?= (int)$stats['total'] ?> listed · <?= (int)$stats['available'] ?> available · <?= (int)$requests['total'] ?> requests received</p>
<?php else: ?><p><?= (int)$stats['total'] ?> requests · <?= (int)$stats['collected'] ?> confirmed pickups</p><?php endif ?>
<p><?= $donor ? 'Review incoming requests and approve a receiver for collection.' : 'Check approvals, find pickup instructions and confirm what you collected.' ?></p>
<a class="button" href="<?= $donor ? 'donate.php' : 'browse.php' ?>"><?= $donor ? 'List food' : 'Find food' ?></a>
<h2><?= $donor ? 'Donation history' : 'Request history' ?></h2>
<?php if (!$items): ?><p><?= $donor ? 'Your first listing will appear here.' : 'Your first request will appear here.' ?></p><?php endif ?>
<div class="grid"><?php foreach (array_slice($items,0,20) as $item):
    $expired = $item['expiry_date'] < today();
    $request = $item['request_status'];
    $status = $request ?: $item['status'];
    if ($expired && in_array($status,['available','pending','approved'],true)) {
        $status = 'expired';
    }
?>
<article><p class="status"><?= h(ucfirst(str_replace('_',' ',$status))) ?></p><h3><?= h($item['food_name']) ?></h3>
<p><?= h($item['quantity']) ?> · <?= h($item['location']) ?><br>Expires <?= h($item['expiry_date']) ?></p>
<?php if ($item['partner']): ?><p><?= $donor ? 'Receiver' : 'Donor' ?> · <?= h($item['partner']) ?></p><?php endif ?>
<?php if ($donor || (!$expired && $request === 'approved')): ?><p><strong>Pickup instructions</strong><br><?= h($item['pickup_details'] ?: 'Use the listing location to arrange collection.') ?></p><?php endif ?>
<?php if ($donor && !$expired && $request === 'pending') { handoffForm('approve','Approve pickup',$item['id'],$item['request_id']); }
if (!$donor && !$expired && $request === 'approved') { handoffForm('collect','Confirm food received',$item['id'],$item['request_id']); }
if (in_array($request,['pending','approved'],true)) { handoffForm('cancel','Release reservation',$item['id'],$item['request_id']); }
if ($donor && !$request && $item['status'] === 'available') { handoffForm('withdraw','Withdraw listing',$item['id']); } ?>
</article><?php endforeach ?></div><?php pager($page,count($items)>20); endPage();
