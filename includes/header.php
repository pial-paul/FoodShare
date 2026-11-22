<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> · FoodShare</title><link rel="stylesheet" href="css/styles.css"></head>
<body><a class="skip" href="#main">Skip to content</a><header><nav class="wrap top" aria-label="Main navigation">
<a class="brand" href="index.php">FoodShare</a><div class="links">
<?php if ($visitor = user()): ?><a href="dashboard.php">Dashboard</a>
<?php if ($visitor['role'] === 'donor'): ?><a href="donate.php">List food</a>
<?php elseif ($visitor['role'] === 'receiver'): ?><a href="browse.php">Find food</a>
<?php else: ?><a href="admin.php">Manage</a><?php endif ?>
<form method="post" action="logout.php"><?= csrf() ?><button class="link">Sign out</button></form>
<?php else: ?><a href="login.php">Sign in</a><a class="button" href="register.php">Join FoodShare</a><?php endif ?>
</div></nav></header><main id="main" class="wrap">
<?php if (isset($_SESSION['flash'])): ?><p class="notice" role="status"><?= h($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
