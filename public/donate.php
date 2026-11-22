<?php
require_once __DIR__ . '/../src/web.php';
require_once __DIR__ . '/../src/photos.php';
$actor = requireUser('donor');
$error = '';
$fields = [
    'food_name' => ['Food name', 100], 'quantity' => ['Quantity and unit', 50],
    'location' => ['Public pickup area', 150], 'pickup_details' => ['Private pickup instructions', 500],
    'handling_notes' => ['Ingredients, allergens and storage information', 500]
];
$values = [];
foreach ($fields as $key => [$label, $limit]) {
    $values[$key] = field($_POST, $key);
}
$date = field($_POST, 'expiry_date');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost();
    try {
        foreach ($fields as $key => [$label, $limit]) {
            if ($values[$key] === '' || mb_strlen($values[$key]) > $limit) {
                throw new DomainException("Enter $label within $limit characters.");
            }
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < today() || strlen($date) !== 10) {
            throw new DomainException('Choose today or a later expiry date.');
        }
        $photo = storePhoto($_FILES['image'] ?? []);
        try {
            db()->execute_query('INSERT INTO donations (donor_id,food_name,quantity,location,pickup_details,handling_notes,expiry_date,image)
                VALUES (?,?,?,?,?,?,?,?)', [$actor['id'], ...array_values($values), $date, $photo]);
        } catch (Throwable $e) {
            if ($photo !== null) {
                unlink(photoDirectory() . '/' . $photo);
            }
            throw $e;
        }
        redirectTo('dashboard.php', 'Your donation is listed.');
    } catch (DomainException $e) {
        $error = $e->getMessage();
    }
}
page('Offer food'); ?>
<h1>Offer surplus food</h1><p>Use a neighborhood for the public area. Only the approved receiver sees your private pickup instructions.</p>
<form method="post" enctype="multipart/form-data" class="panel"><?= csrf() ?><?php showError($error) ?>
<?php foreach ($fields as $key => [$label, $limit]): ?>
<label for="<?= h($key) ?>"><?= h($label) ?></label>
<?php if ($key === 'handling_notes'): ?><p>Include known ingredients and how the food was stored. Say which details are unknown.</p><?php endif ?>
<input id="<?= h($key) ?>" name="<?= h($key) ?>" maxlength="<?= $limit ?>" value="<?= h($values[$key]) ?>" required>
<?php endforeach ?>
<label for="expiry">Expiry date (<?= h(getenv('FOODSHARE_TIMEZONE') ?: 'UTC') ?>)</label>
<input type="date" id="expiry" name="expiry_date" min="<?= today() ?>" value="<?= h($date) ?>" required>
<label for="image">Optional food photo</label><p>JPG or PNG, up to 5 MB. Use a food-only photo without location metadata.</p>
<input type="file" id="image" name="image" accept="image/jpeg,image/png">
<div class="actions"><button>List donation</button></div></form><?php endPage();
