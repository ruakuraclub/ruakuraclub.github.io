<?php
declare(strict_types=1);

// Replace the logged-in user's authenticator (e.g. new phone).
// The old one keeps working until the new one is confirmed.

require dirname(__DIR__) . '/src/bootstrap.php';

$user = require_login();
$secret = $_SESSION['new_totp_secret'] ??= tfa()->createSecret();
$error = null;

if (is_post()) {
    csrf_check();
    if (!password_verify((string) ($_POST['current_password'] ?? ''), $user['password_hash'])) {
        record_failure($user, 'wrong_current_password');
        $error = 'Your current password is incorrect.';
    } else {
        // New secret, so codes used with the old one don't count against it.
        $timeslice = verify_totp(['totp_last_timeslice' => null] + $user, $secret, (string) ($_POST['code'] ?? ''));
        if ($timeslice === null) {
            $error = "That code didn't match. Check the app is showing the code for the new entry and try again.";
        } else {
            db()->prepare('UPDATE users SET totp_secret = ?, totp_last_timeslice = ? WHERE id = ?')
                ->execute([$secret, $timeslice, $user['id']]);
            unset($_SESSION['new_totp_secret']);
            auth_log('authenticator_changed', $user);
            flash('Your new authenticator is set up. You can delete the old entry from your app.');
            redirect('profile.php');
        }
    }
}

render('profile-totp', [
    'title'  => 'New authenticator',
    'user'   => $user,
    'qr'     => tfa()->getQRCodeImageAsDataUri($user['username'], $secret, 220),
    'secret' => trim(chunk_split($secret, 4, ' ')),
    'error'  => $error,
]);
