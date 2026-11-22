<?php
require_once __DIR__ . '/../src/web.php';
if (user()) {
    redirectTo('dashboard.php');
}
$error = '';
$name = field($_POST, 'name');
$email = field($_POST, 'email');
$role = field($_POST, 'role') ?: field($_GET, 'role');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost();
    $password = $_POST['password'] ?? null;
    if ($name === '' || mb_strlen($name) > 100 || strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a name and valid email, each within 100 characters.';
    } elseif (!in_array($role, ['donor', 'receiver'], true)) {
        $error = 'Choose a donor or receiver account.';
    } elseif (!is_string($password) || str_contains($password, "\0") || mb_strlen($password) < 12 || strlen($password) > 72) {
        $error = 'Use at least 12 characters and at most 72 bytes for your password.';
    } elseif ($password !== ($_POST['confirm_password'] ?? null)) {
        $error = 'The passwords must match.';
    } else {
        try {
            db()->execute_query('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)',
                [$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            signIn(db()->insert_id);
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() !== 1062) {
                throw $e;
            }
            $error = 'Unable to register with these details. If you already have an account, sign in.';
        }
    }
}
page('Create an account'); ?>
<h1>Create an account</h1><form method="post" class="panel"><?= csrf() ?><?php showError($error) ?>
<label for="name">Name or organization</label><input id="name" name="name" required maxlength="100" value="<?= h($name) ?>" autocomplete="name">
<label for="email">Email</label><input id="email" name="email" type="email" required maxlength="100" value="<?= h($email) ?>" autocomplete="email">
<label for="role">How will you use FoodShare?</label><select id="role" name="role" required><option value="">Choose a role</option>
<option value="donor" <?= $role === 'donor' ? 'selected' : '' ?>>Donor — offer food</option>
<option value="receiver" <?= $role === 'receiver' ? 'selected' : '' ?>>Receiver — collect food</option></select>
<label for="password">Password</label><p id="rules">At least 12 characters, up to 72 bytes.</p>
<input id="password" name="password" type="password" required autocomplete="new-password" aria-describedby="rules">
<label for="confirm">Confirm password</label><input id="confirm" name="confirm_password" type="password" required autocomplete="new-password">
<div class="actions"><button>Create account</button><a href="login.php">Already registered?</a></div></form><?php endPage();
