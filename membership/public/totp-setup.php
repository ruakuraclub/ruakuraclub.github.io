<?php
declare(strict_types=1);

// First login: link an authenticator app before the user can get in.

require dirname(__DIR__) . '/src/bootstrap.php';

$user = pending_user();
if (!$user) {
    redirect('login.php');
}
if ($user['totp_secret']) {
    redirect('totp.php');
}

$secret = $_SESSION['totp_setup_secret'] ??= tfa()->createSecret();
$error = null;

if (is_post()) {
    csrf_check();
    $timeslice = verify_totp($user, $secret, (string) ($_POST['code'] ?? ''));
    if ($timeslice !== null) {
        db()->prepare('UPDATE users SET totp_secret = ? WHERE id = ? AND totp_secret IS NULL')
            ->execute([$secret, $user['id']]);
        auth_log('authenticator_set_up', $user);
        complete_login($user, $timeslice);
        redirect('./');
    }
    $error = "That code didn't match. Check the app is showing the code for this account and try again.";
}

render('totp-setup', [
    'title'  => 'Set up your authenticator app',
    'qr'     => tfa()->getQRCodeImageAsDataUri($user['username'], $secret, 220),
    'secret' => trim(chunk_split($secret, 4, ' ')),
    'error'  => $error,
]);
