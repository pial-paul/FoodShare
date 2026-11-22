<?php
require_once __DIR__ . '/../src/web.php';
page('Community food redistribution');
?>
<section class="hero"><div><p class="eyebrow">Community food redistribution</p>
<h1>Share surplus food while it’s fresh.</h1>
<p class="intro">List food your kitchen can spare, or arrange a pickup for your community. Track each donation from the first request to the handoff.</p>
<div class="actions"><?php if (user()): ?><a class="button" href="dashboard.php">Open dashboard</a>
<?php else: ?><a class="button" href="register.php?role=donor">Offer surplus food</a>
<a class="button secondary" href="register.php?role=receiver">Find a donation</a><?php endif ?></div></div>
<img src="img/food-platter.png" alt="" width="520" height="390"></section>
<h2>How a pickup works</h2><div class="grid">
<article><h3>List the food</h3><p>Share the quantity, pickup area, expiry date and handling information.</p></article>
<article><h3>Arrange a pickup</h3><p>A receiver requests the listing. The donor approves and shares pickup details.</p></article>
<article><h3>Confirm receipt</h3><p>The receiver confirms collection and can record the weight they actually received.</p></article></div>
<?php endPage();
