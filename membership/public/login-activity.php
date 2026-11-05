<?php
declare(strict_types=1);

// Login attempts and account changes, newest first.

require dirname(__DIR__) . '/src/bootstrap.php';

const LOGIN_ACTIVITY_LIMIT = 500;

$user = require_login();

$users = db()->query('SELECT id, display_name, username FROM users ORDER BY display_name')->fetchAll();

$who = (string) ($_GET['who'] ?? '');
$userFilter = match (true) {
    $who === 'unknown'                                                      => 'unknown',
    ctype_digit($who) && in_array((int) $who, array_column($users, 'id'))   => (int) $who,
    default                                                                 => null,
};
$group = isset(AUTH_EVENT_GROUPS[$_GET['group'] ?? '']) ? $_GET['group'] : null;

render('login-activity', [
    'title'   => 'Login activity',
    'user'    => $user,
    'users'   => $users,
    'who'     => $userFilter === null ? '' : (string) $userFilter,
    'group'   => $group ?? '',
    'entries' => auth_log_entries($userFilter, $group, LOGIN_ACTIVITY_LIMIT),
    'limit'   => LOGIN_ACTIVITY_LIMIT,
]);
