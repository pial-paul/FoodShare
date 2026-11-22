<?php
require_once __DIR__ . '/../src/web.php';
require_once __DIR__ . '/../includes/action.php';
requireUser('receiver');
$search = mb_substr(field($_GET, 'search'), 0, 100);
$area = mb_substr(field($_GET, 'location'), 0, 150);
$sort = field($_GET, 'sort');
$orders = ['expiry_asc' => 'd.expiry_date ASC', 'expiry_desc' => 'd.expiry_date DESC', 'newest' => 'd.id DESC'];
if (!isset($orders[$sort])) {
    $sort = 'expiry_asc';
}
$page = pageNumber();
$sql = "SELECT d.id,d.food_name,d.quantity,d.location,d.expiry_date,d.image,d.handling_notes,u.name
    FROM donations d JOIN users u ON u.id=d.donor_id WHERE d.status='available' AND d.expiry_date>=? AND u.disabled_at IS NULL";
$params = [today()];
foreach ([' AND (d.food_name LIKE ? OR d.location LIKE ?)' => [$search,$search], ' AND d.location LIKE ?' => [$area]] as $clause => $values) {
    if ($values[0] !== '') {
        $sql .= $clause;
        foreach ($values as $value) {
            $params[] = '%' . $value . '%';
        }
    }
}
$items = rows($sql . ' ORDER BY ' . $orders[$sort] . ', d.id DESC LIMIT 21 OFFSET ' . (($page-1)*20), $params);
page('Find food'); ?>
<h1>Find food</h1><p>See available donations and check handling details before requesting a pickup.</p>
<form method="get" class="filters"><label>Food or area<input name="search" maxlength="100" value="<?= h($search) ?>"></label>
<label>Pickup area<input name="location" maxlength="150" value="<?= h($area) ?>"></label>
<label>Sort by<select name="sort"><?php foreach (['expiry_asc'=>'Soonest expiry','newest'=>'Newest first','expiry_desc'=>'Latest expiry'] as $key=>$label): ?>
<option value="<?= $key ?>" <?= $key === $sort ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select></label>
<button>Filter</button><a href="browse.php">Clear</a></form>
<?php if (!$items): ?><p>No available donations match this page. Clear the filters or check again later.</p><?php endif ?>
<div class="grid"><?php foreach (array_slice($items,0,20) as $item): ?><article>
<?php if ($item['image']): ?><img class="photo" src="photo.php?id=<?= (int)$item['id'] ?>" alt="" loading="lazy"><?php endif ?>
<p class="status">Expires <?= h($item['expiry_date']) ?></p><h2><?= h($item['food_name']) ?></h2>
<p><?= h($item['quantity']) ?> · <?= h($item['location']) ?></p><p>Offered by <?= h($item['name']) ?></p>
<p><?= h($item['handling_notes'] ?: 'Handling information unavailable. Check with the donor before collection.') ?></p>
<?php handoffForm('request','Request pickup',$item['id']); ?></article><?php endforeach ?></div>
<?php pager($page,count($items)>20,compact('search','sort') + ['location'=>$area]); endPage();
