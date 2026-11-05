<?php
declare(strict_types=1);

// Add, deactivate and reactivate logins. All logins have the same access.

require dirname(__DIR__) . '/src/bootstrap.php';

$user = require_login();

$values = ['username' => '', 'display_name' => ''];
$errors = [];

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add') {
        $values['username'] = trim((string) ($_POST['username'] ?? ''));
        $values['display_name'] = trim((string) ($_POST['display_name'] ?? ''));
        $errors = create_user(
            $values['username'],
            $values['display_name'],
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['password_confirm'] ?? ''),
        );
        if (!$errors) {
            flash("Added {$values['display_name']}. Give them the password privately; they'll set up their authenticator app when they first log in.");
            redirect('users.php');
        }
    } elseif ($action === 'deactivate' || $action === 'activate') {
        $target = find_user_by_id((int) ($_POST['user_id'] ?? 0));
        if ($target && (int) $target['id'] !== (int) $user['id']) {
            set_user_active((int) $target['id'], $action === 'activate');
            flash(($action === 'activate' ? 'Reactivated ' : 'Deactivated ') . $target['display_name'] . '.');
        }
        redirect('users.php');
    }
}

$users = db()->query(
    'SELECT id, username, display_name, totp_secret IS NOT NULL AS has_totp, is_active, last_login_at, locked_until
     FROM users ORDER BY is_active DESC, display_name'
)->fetchAll();

$flash = take_flash();

render('users', [
    'title'  => 'Users',
    'user'   => $user,
    'users'  => $users,
    'values' => $values,
    'errors' => $errors,
    'flash'  => $flash,
]);
