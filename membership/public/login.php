<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if (current_user()) {
    redirect('./');
}

$username = '';
$error = null;

if (is_post()) {
    csrf_check();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $user = $username !== '' ? find_user_by_username($username) : null;

    if ($user && $user['is_active'] && !is_locked($user) && password_verify($password, $user['password_hash'])) {
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        start_pending_login($user);
        redirect($user['totp_secret'] ? 'totp.php' : 'totp-setup.php');
    }

    // Every failure gets the same message; the log records what actually happened.
    if (!$user) {
        password_hash($password, PASSWORD_DEFAULT);   // same response time whether or not the username exists
        if ($username !== '') {
            auth_log('unknown_user', null, $username);
        }
    } elseif (is_locked($user)) {
        auth_log('attempt_while_locked', $user);      // ignored: doesn't count or extend the lockout
    } elseif (!$user['is_active']) {
        auth_log('inactive_user', $user);
    } else {
        record_failure($user, 'wrong_password');
    }
    $error = 'Incorrect username or password.';
}

render('login', [
    'title'    => 'Log in',
    'username' => $username,
    'error'    => $error,
    'created'  => isset($_GET['created']),
]);
