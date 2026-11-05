<?php
declare(strict_types=1);

// Help another user: change their name, set a new password, reset a lost
// authenticator, or unlock them after too many failed attempts.
// Your own account is managed from My profile.

require dirname(__DIR__) . '/src/bootstrap.php';

$user = require_login();

$target = find_user_by_id((int) ($_GET['id'] ?? 0));
if (!$target) {
    flash('That user was not found.');
    redirect('users.php');
}
if ((int) $target['id'] === (int) $user['id']) {
    redirect('profile.php');
}

$self = 'user-edit.php?id=' . (int) $target['id'];
$errors = [];
$displayName = $target['display_name'];

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'name') {
        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        if ($displayName === '' || mb_strlen($displayName) > 100) {
            $errors[] = 'Enter a name (up to 100 characters).';
        } else {
            db()->prepare('UPDATE users SET display_name = ? WHERE id = ?')->execute([$displayName, $target['id']]);
            auth_log('name_changed', $target);
            flash('Name saved.');
            redirect($self);
        }
    } elseif ($action === 'password') {
        $password = (string) ($_POST['password'] ?? '');
        $errors = validate_password($password, (string) ($_POST['password_confirm'] ?? ''));
        if (!$errors) {
            db()->prepare('UPDATE users SET password_hash = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $target['id']]);
            auth_log('password_set', $target);
            flash("New password set for {$target['display_name']}. Give it to them privately; they can change it under My profile.");
            redirect($self);
        }
    } elseif ($action === 'reset_totp') {
        if (empty($_POST['confirm_reset'])) {
            $errors[] = 'Tick the box to confirm the authenticator reset.';
        } else {
            db()->prepare('UPDATE users SET totp_secret = NULL, totp_last_timeslice = NULL WHERE id = ?')->execute([$target['id']]);
            auth_log('authenticator_reset', $target);
            flash("Authenticator reset. {$target['display_name']} will scan a new QR code the next time they log in, so ask them to log in soon.");
            redirect($self);
        }
    } elseif ($action === 'unlock') {
        db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?')->execute([$target['id']]);
        auth_log('unlocked', $target);
        flash('Unlocked.');
        redirect($self);
    }
}

render('user-edit', [
    'title'       => $target['display_name'],
    'user'        => $user,
    'target'      => $target,
    'displayName' => $displayName,
    'locked'      => is_locked($target),
    'entries'     => auth_log_entries((int) $target['id'], null, 15),
    'errors'      => $errors,
    'flash'       => take_flash(),
]);
