<?php
declare(strict_types=1);

// Loaded at the top of every page in public/.

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/auth.php';

date_default_timezone_set('Pacific/Auckland');

function config(): array
{
    static $config = null;
    if ($config === null) {
        $file = dirname(__DIR__) . '/config.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit('config.php is missing. Copy config.example.php to config.php and fill it in.');
        }
        $config = require $file;
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config()['db'];
        $pdo = new PDO($c['dsn'], $c['user'] ?? null, $c['password'] ?? null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Your session expired. Please go back, reload the page and try again.');
    }
}

/** A one-off message shown on the next page (after a redirect). */
function flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function take_flash(): ?string
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

/** A valid Y-m-d date, or '' (not given). Anything else returns null. */
function clean_date(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    $d = DateTime::createFromFormat('!Y-m-d', $value);
    return ($d && $d->format('Y-m-d') === $value) ? $value : null;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/** Renders templates/{$template}.php inside templates/layout.php and stops. */
function render(string $template, array $vars = []): never
{
    extract($vars);
    ob_start();
    require dirname(__DIR__) . "/templates/{$template}.php";
    $content = ob_get_clean();
    require dirname(__DIR__) . '/templates/layout.php';
    exit;
}

// ---- Session and security headers ----

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
session_name('rcc_membership');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
