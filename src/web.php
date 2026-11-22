<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

ini_set('display_errors', '0');
set_exception_handler(static function (Throwable $e): void {
    $reference = bin2hex(random_bytes(6));
    error_log(sprintf('FoodShare %s %s code=%s at %s:%d', $reference, $e::class, $e->getCode(),
        basename($e->getFile()), $e->getLine()));
    http_response_code(500);
    echo 'FoodShare could not finish this request. Reference ' . $reference;
});
header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
ini_set('session.use_strict_mode', '1');
session_name('foodshare');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax',
    'secure' => getenv('FOODSHARE_HTTPS') === '1' || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]);
session_start();

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function field(array $source, string $name): string
{
    return is_string($source[$name] ?? null) ? trim($source[$name]) : '';
}

function redirectTo(string $url, string $message = ''): never
{
    if ($message !== '') {
        $_SESSION['flash'] = $message;
    }
    header('Location: ' . $url, true, 303);
    exit;
}

function user(): ?array
{
    static $user;
    if (!$user && isset($_SESSION['user_id'])) {
        $user = rows('SELECT id,name,role FROM users WHERE id=? AND disabled_at IS NULL', [$_SESSION['user_id']])[0] ?? null;
        if (!$user) {
            $_SESSION = [];
        }
    }
    return $user;
}

function requireUser(?string $role = null): array
{
    $user = user();
    if (!$user) {
        redirectTo('login.php', 'Sign in to continue.');
    }
    if ($role !== null && $user['role'] !== $role) {
        http_response_code(403);
        exit('This page is restricted to the appropriate account role.');
    }
    return $user;
}

function signIn(int $id): never
{
    session_regenerate_id(true);
    $_SESSION = ['user_id' => $id];
    redirectTo('dashboard.php');
}

function csrf(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}

function requirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Submit the form to continue.');
    }
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], field($_POST, 'csrf'))) {
        http_response_code(403);
        exit('Your form has expired. Go back, refresh the page and try again.');
    }
}

function page(string $title): void
{
    require __DIR__ . '/../includes/header.php';
}

function endPage(): void
{
    require __DIR__ . '/../includes/footer.php';
}

function showError(string $error): void
{
    if ($error !== '') {
        echo '<p class="notice" role="alert">' . h($error) . '</p>';
    }
}

function pageNumber(): int
{
    return max(1, min(1000000, (int)field($_GET, 'page')));
}

function pager(int $page, bool $more, array $filters = []): void
{
    echo '<nav class="pager" aria-label="Result pages">';
    foreach (['Previous' => $page - 1, 'Next' => $page + 1] as $label => $dest) {
        if ($dest > 0 && ($dest < $page || $more)) {
            echo '<a href="?' . h(http_build_query($filters + ['page' => $dest])) . '">' . h($label) . '</a>';
        }
    }
    echo '</nav>';
}
