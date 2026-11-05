<?php
declare(strict_types=1);

// Login is two steps: password, then a 6-digit code from an authenticator app (TOTP).
// After the password step the user is "pending" until the code is entered.

use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

const MAX_FAILED_ATTEMPTS   = 5;
const LOCKOUT_MINUTES       = 15;
const PENDING_LOGIN_SECONDS = 300;   // time allowed between password and code
const IDLE_TIMEOUT_SECONDS  = 1800;  // logged out after 30 minutes without activity

const AUTH_LOG_KEEP_DAYS = 365;

// Logged events: code => [label, group]. Groups are used to filter the Login activity page.
const AUTH_EVENTS = [
    'login'                   => ['Logged in', 'success'],
    'logout'                  => ['Logged out', 'success'],
    'wrong_password'          => ['Wrong password', 'failure'],
    'unknown_user'            => ['Unknown username', 'failure'],
    'inactive_user'           => ['Login by deactivated user', 'failure'],
    'attempt_while_locked'    => ['Attempt while locked', 'failure'],
    'wrong_code'              => ['Wrong authenticator code', 'failure'],
    'locked_out'              => ['Locked out (too many failures)', 'failure'],
    'wrong_current_password'  => ['Wrong current password (profile)', 'failure'],
    'authenticator_set_up'    => ['Authenticator set up', 'change'],
    'authenticator_changed'   => ['Authenticator changed', 'change'],
    'authenticator_reset'     => ['Authenticator reset', 'change'],
    'password_changed'        => ['Password changed', 'change'],
    'password_set'            => ['Password set by another user', 'change'],
    'unlocked'                => ['Unlocked', 'change'],
    'name_changed'            => ['Name changed', 'change'],
    'user_added'              => ['User added', 'change'],
    'user_deactivated'        => ['User deactivated', 'change'],
    'user_reactivated'        => ['User reactivated', 'change'],
];
const AUTH_EVENT_GROUPS = ['failure' => 'Failures and lockouts', 'success' => 'Logins and logouts', 'change' => 'Account changes'];

/**
 * Records a login attempt or account change. $user is the account concerned (if known);
 * $username is what was typed at login. Whoever is logged in is recorded as the actor
 * when they're changing someone else's account.
 */
function auth_log(string $event, ?array $user = null, ?string $username = null): void
{
    $userId = $user ? (int) $user['id'] : null;
    $actor = isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] !== $userId ? (int) $_SESSION['user_id'] : null;
    db()->prepare(
        'INSERT INTO auth_log (created_at, event, user_id, username, actor_user_id, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        date('Y-m-d H:i:s'),
        $event,
        $userId,
        mb_substr($username ?? ($user['username'] ?? ''), 0, 100) ?: null,
        $actor,
        mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
        mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
    ]);
    db()->prepare('DELETE FROM auth_log WHERE created_at < ?')
        ->execute([date('Y-m-d H:i:s', time() - AUTH_LOG_KEEP_DAYS * 86400)]);
}

/**
 * Log entries, newest first, with the names of the account and of whoever made the change.
 * $userFilter: a user id, 'unknown' (usernames that don't exist), or null for everyone.
 * $group: a key of AUTH_EVENT_GROUPS, or null for all events.
 */
function auth_log_entries(int|string|null $userFilter, ?string $group, int $limit): array
{
    $sql = 'SELECT l.*, u.display_name, a.display_name AS actor_name
              FROM auth_log l
              LEFT JOIN users u ON u.id = l.user_id
              LEFT JOIN users a ON a.id = l.actor_user_id
             WHERE 1 = 1';
    $params = [];
    if ($userFilter === 'unknown') {
        $sql .= ' AND l.user_id IS NULL';
    } elseif (is_int($userFilter)) {
        $sql .= ' AND l.user_id = ?';
        $params[] = $userFilter;
    }
    if ($group !== null) {
        $events = array_keys(array_filter(AUTH_EVENTS, fn($e) => $e[1] === $group));
        $sql .= ' AND l.event IN (' . implode(', ', array_fill(0, count($events), '?')) . ')';
        array_push($params, ...$events);
    }
    $stmt = db()->prepare($sql . ' ORDER BY l.id DESC LIMIT ' . max(1, $limit));
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** "Chrome on Windows" from a browser's user-agent string (good enough to spot an odd device). */
function browser_summary(?string $userAgent): string
{
    if (!$userAgent) {
        return '';
    }
    $browser = match (true) {
        str_contains($userAgent, 'Edg/')     => 'Edge',
        str_contains($userAgent, 'Firefox/') => 'Firefox',
        str_contains($userAgent, 'Chrome/')  => 'Chrome',
        str_contains($userAgent, 'Safari/')  => 'Safari',
        default                              => 'Other browser',
    };
    $device = match (true) {
        str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iPhone/iPad',
        str_contains($userAgent, 'Android')   => 'Android',
        str_contains($userAgent, 'Windows')   => 'Windows',
        str_contains($userAgent, 'Macintosh') => 'Mac',
        str_contains($userAgent, 'Linux')     => 'Linux',
        default                               => '',
    };
    return $browser . ($device ? ' on ' . $device : '');
}

function tfa(): TwoFactorAuth
{
    static $tfa = null;
    return $tfa ??= new TwoFactorAuth(new BaconQrCodeProvider(1, '#ffffff', '#000000', 'svg'), config()['app_name']);
}

function find_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function find_user_by_username(string $username): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

/** Validates and creates a login. Returns the problems found; an empty list means it was created. */
function create_user(string $username, string $displayName, string $password, string $passwordConfirm): array
{
    $errors = [];
    if (!preg_match('/^[A-Za-z0-9._@-]{3,100}$/', $username)) {
        $errors[] = 'Username must be 3–100 characters: letters, numbers, . _ @ or -.';
    } elseif (find_user_by_username($username)) {
        $errors[] = 'That username is already taken.';
    }
    if ($displayName === '') {
        $errors[] = 'Enter a name.';
    }
    $errors = array_merge($errors, validate_password($password, $passwordConfirm));
    if (!$errors) {
        db()->prepare('INSERT INTO users (username, display_name, password_hash) VALUES (?, ?, ?)')
            ->execute([$username, $displayName, password_hash($password, PASSWORD_DEFAULT)]);
        auth_log('user_added', find_user_by_username($username));
    }
    return $errors;
}

function validate_password(string $password, string $passwordConfirm): array
{
    $errors = [];
    if (mb_strlen($password) < 12) {
        $errors[] = 'Password must be at least 12 characters.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = "The passwords don't match.";
    }
    return $errors;
}

function set_user_active(int $id, bool $active): void
{
    db()->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
    auth_log($active ? 'user_reactivated' : 'user_deactivated', find_user_by_id($id));
}

function is_locked(array $user): bool
{
    return $user['locked_until'] !== null && strtotime($user['locked_until']) > time();
}

/**
 * Logs a failed password or code ($event) and counts it; locks the account
 * after MAX_FAILED_ATTEMPTS.
 */
function record_failure(array $user, string $event): void
{
    auth_log($event, $user);
    $attempts = (int) $user['failed_attempts'] + 1;
    if ($attempts >= MAX_FAILED_ATTEMPTS) {
        $stmt = db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = ? WHERE id = ?');
        $stmt->execute([date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60), $user['id']]);
        auth_log('locked_out', $user);
    } else {
        $stmt = db()->prepare('UPDATE users SET failed_attempts = ? WHERE id = ?');
        $stmt->execute([$attempts, $user['id']]);
    }
}

/** Password accepted: remember who is logging in until they enter their code. */
function start_pending_login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION = ['pending_user_id' => (int) $user['id'], 'pending_at' => time()];
}

function pending_user(): ?array
{
    $id = $_SESSION['pending_user_id'] ?? null;
    if ($id === null || time() - ($_SESSION['pending_at'] ?? 0) > PENDING_LOGIN_SECONDS) {
        return null;
    }
    $user = find_user_by_id((int) $id);
    return ($user && $user['is_active'] && !is_locked($user)) ? $user : null;
}

/**
 * Checks a 6-digit code. Returns the code's timeslice, or null if it's wrong
 * or has already been used.
 */
function verify_totp(array $user, string $secret, string $code): ?int
{
    $code = preg_replace('/\s+/', '', $code);
    if (!preg_match('/^\d{6}$/', $code)) {
        return null;
    }
    $timeslice = 0;
    if (!tfa()->verifyCode($secret, $code, 1, null, $timeslice)) {
        return null;
    }
    if ($user['totp_last_timeslice'] !== null && $timeslice <= (int) $user['totp_last_timeslice']) {
        return null;
    }
    return $timeslice;
}

function complete_login(array $user, int $timeslice): void
{
    $stmt = db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL, totp_last_timeslice = ?, last_login_at = ? WHERE id = ?');
    $stmt->execute([$timeslice, date('Y-m-d H:i:s'), $user['id']]);
    session_regenerate_id(true);
    $_SESSION = ['user_id' => (int) $user['id'], 'last_activity' => time()];
    auth_log('login', $user);
}

function logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function current_user(): ?array
{
    $id = $_SESSION['user_id'] ?? null;
    if ($id === null) {
        return null;
    }
    if (time() - ($_SESSION['last_activity'] ?? 0) > IDLE_TIMEOUT_SECONDS) {
        logout();
        return null;
    }
    $user = find_user_by_id((int) $id);
    if (!$user || !$user['is_active']) {
        logout();
        return null;
    }
    $_SESSION['last_activity'] = time();
    return $user;
}

function require_login(): array
{
    return current_user() ?? redirect('login.php');
}
