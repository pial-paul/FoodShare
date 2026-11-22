<?php
require_once __DIR__ . '/../src/web.php';
require_once __DIR__ . '/../includes/action.php';
$actor = requireUser('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost();
    $action = field($_POST,'action');
    if (in_array($action,['suspend','restore'],true)) {
        $value = $action === 'suspend' ? 'UTC_TIMESTAMP()' : 'NULL';
        db()->execute_query("UPDATE users SET disabled_at=$value WHERE id=? AND role IN ('donor','receiver')",
            [(int)field($_POST,'user_id')]);
    }
    redirectTo('admin.php?tab=users', 'Account settings saved.');
}
$tab = field($_GET,'tab');
$queries = [
    'users' => 'SELECT id,name,email,role,disabled_at FROM users ORDER BY id DESC',
    'donations' => 'SELECT d.id,d.food_name,d.status,d.expiry_date,u.name,r.id request_id,r.status request_status
        FROM donations d JOIN users u ON u.id=d.donor_id LEFT JOIN requests r ON r.donation_id=d.id AND r.assignment_slot=1 ORDER BY d.id DESC',
    'requests' => 'SELECT r.id,r.status,d.food_name,u.name,r.completed_at,r.received_kg FROM requests r
        JOIN donations d ON d.id=r.donation_id JOIN users u ON u.id=r.receiver_id ORDER BY r.id DESC'
];
if (!isset($queries[$tab])) {
    $tab = 'users';
}
$page = pageNumber();
$items = rows($queries[$tab] . ' LIMIT 21 OFFSET ' . (($page - 1)*20));
$impact = rows("SELECT COUNT(*) pickups,COUNT(received_kg) weighed,SUM(received_kg) kg FROM requests
    WHERE status='completed' AND completed_at IS NOT NULL")[0];
page('Administration'); ?>
<h1>Administration</h1><h2>Recorded handoffs</h2>
<p><?= (int)$impact['pickups'] ?> receiver confirmations · <?= (int)$impact['weighed'] ?> with measured weight ·
<?= $impact['kg'] === null ? 'No weight recorded' : h($impact['kg']) . ' kg reported received' ?></p>
<nav class="links" aria-label="Management sections"><?php foreach (array_keys($queries) as $key): ?>
<a href="?tab=<?= $key ?>" <?= $key === $tab ? 'aria-current="page"' : '' ?>><?= ucfirst($key) ?></a><?php endforeach ?></nav>
<h2><?= ucfirst($tab) ?></h2><?php if (!$items): ?><p>No records on this page.</p><?php endif ?><div class="grid">
<?php foreach (array_slice($items,0,20) as $item): ?><article>
<?php if ($tab === 'users'): ?><h3><?= h($item['name']) ?></h3><p><?= h($item['email']) ?> · <?= h($item['role']) ?> · <?= $item['disabled_at'] ? 'Suspended' : 'Active' ?></p>
<?php if ($item['role'] !== 'admin'): ?><form method="post"><?= csrf() ?><input type="hidden" name="user_id" value="<?= (int)$item['id'] ?>">
<button name="action" value="<?= $item['disabled_at'] ? 'restore' : 'suspend' ?>"><?= $item['disabled_at'] ? 'Restore account' : 'Suspend account' ?></button></form><?php endif ?>
<?php else: ?><h3><?= h($item['food_name']) ?></h3><p><?= h($item['name']) ?> · <?= h($item['status']) ?></p>
<?php if ($tab === 'donations' && $item['status'] === 'available') { handoffForm('withdraw','Withdraw listing',$item['id']); }
if ($tab === 'donations' && in_array($item['request_status'],['pending','approved'],true)) { handoffForm('cancel','Release reservation',$item['id'],$item['request_id']); } ?>
<?php endif ?></article><?php endforeach ?></div><?php pager($page,count($items)>20,compact('tab')); endPage();
