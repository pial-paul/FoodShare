<?php
function handoffForm(string $action, string $label, int $donationId, int $requestId = 0): void
{ ?>
<form method="post" action="request.php"><?= csrf() ?>
<input type="hidden" name="action" value="<?= h($action) ?>"><input type="hidden" name="donation_id" value="<?= $donationId ?>">
<input type="hidden" name="request_id" value="<?= $requestId ?>">
<?php if ($action === 'collect'): ?><label>Weight received in kg (optional, measured only)
<input type="number" name="received_kg" step=".01" min=".01" max="999999.99"></label><?php endif ?>
<button><?= h($label) ?></button></form>
<?php }
