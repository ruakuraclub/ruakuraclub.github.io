<?php
declare(strict_types=1);

// The logged-in user's own account: change password, link to new authenticator setup.

require dirname(__DIR__) . '/src/bootstrap.php';

$user = require_login();
$errors = [];

if (is_post()) {
    csrf_check();
    if (!password_verify((string) ($_POST['current_password'] ?? ''), $user['password_hash'])) {
        record_failure($user, 'wrong_current_password');
        $errors[] = 'Your current password is incorrect.';
    } else {
        $new = (string) ($_POST['new_password'] ?? '');
        $errors = validate_password($new, (string) ($_POST['new_password_confirm'] ?? ''));
        if (!$errors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            auth_log('password_changed', $user);
            session_regenerate_id(true);
            flash('Your password has been changed.');
            redirect('profile.php');
        }
    }
}

render('profile', [
    'title'  => 'My profile',
    'user'   => $user,
    'errors' => $errors,
    'flash'  => take_flash(),
]);
