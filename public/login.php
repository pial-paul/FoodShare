<?php
require_once __DIR__ . '/../src/web.php';
if (user()) {
    redirectTo('dashboard.php');
}
$error = '';
$email = field($_POST, 'email');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost();
    $bucket = hash('sha256', strtolower(substr($email, 0, 100)) . '|' . $_SERVER['REMOTE_ADDR']);
    db()->execute_query("INSERT INTO login_limits (bucket,attempts,resets_at) VALUES (?,1,UTC_TIMESTAMP()+INTERVAL 15 MINUTE)
        ON DUPLICATE KEY UPDATE attempts=IF(resets_at<=UTC_TIMESTAMP(),1,attempts+1),
        resets_at=IF(resets_at<=UTC_TIMESTAMP(),UTC_TIMESTAMP()+INTERVAL 15 MINUTE,resets_at)", [$bucket]);
    if (random_int(1, 100) === 1) {
        db()->query('DELETE FROM login_limits WHERE resets_at<=UTC_TIMESTAMP() LIMIT 500');
    }
    $attempts = rows('SELECT attempts FROM login_limits WHERE bucket=?', [$bucket])[0]['attempts'] ?? 1;
    if ($attempts > 10) {
        http_response_code(429);
        header('Retry-After: 900');
        $error = 'Too many attempts. Try again in 15 minutes.';
    } else {
        $account = rows('SELECT id,password FROM users WHERE email=? AND disabled_at IS NULL', [$email])[0] ?? null;
        $password = $_POST['password'] ?? null;
        // Dummy bcrypt value keeps unknown accounts on a comparable verification path.
        $hash = $account['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (is_string($password) && !str_contains($password, "\0") && password_verify($password, $hash) && $account) {
            db()->execute_query('DELETE FROM login_limits WHERE bucket=?', [$bucket]);
            signIn((int)$account['id']);
        }
        $error = 'Invalid email or password.';
    }
}
page('Sign in'); ?>
<h1>Sign in</h1><form method="post" class="panel"><?= csrf() ?><?php showError($error) ?>
<label for="email">Email</label><input type="email" id="email" name="email" autocomplete="username" required value="<?= h($email) ?>">
<label for="password">Password</label><input type="password" id="password" name="password" autocomplete="current-password" required>
<div class="actions"><button>Sign in</button><a href="register.php">Create an account</a></div></form><?php endPage();
