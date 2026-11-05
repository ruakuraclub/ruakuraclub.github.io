<?php
declare(strict_types=1);

// Creates the first login. Only works while the users table is empty,
// so it switches itself off once the first account exists.

require dirname(__DIR__) . '/src/bootstrap.php';

if ((int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    http_response_code(404);
    exit('Not found');
}

$values = ['username' => '', 'display_name' => ''];
$errors = [];

if (is_post()) {
    csrf_check();
    $values['username'] = trim((string) ($_POST['username'] ?? ''));
    $values['display_name'] = trim((string) ($_POST['display_name'] ?? ''));
    $errors = create_user(
        $values['username'],
        $values['display_name'],
        (string) ($_POST['password'] ?? ''),
        (string) ($_POST['password_confirm'] ?? ''),
    );
    if (!$errors) {
        redirect('login.php?created=1');
    }
}

render('setup', ['title' => 'Create the first login', 'values' => $values, 'errors' => $errors]);
