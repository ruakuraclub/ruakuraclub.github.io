<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$user = pending_user();
if (!$user || !$user['totp_secret']) {
    redirect('login.php');
}

$error = null;

if (is_post()) {
    csrf_check();
    $timeslice = verify_totp($user, $user['totp_secret'], (string) ($_POST['code'] ?? ''));
    if ($timeslice !== null) {
        complete_login($user, $timeslice);
        redirect('./');
    }
    record_failure($user, 'wrong_code');
    if (!pending_user()) {   // that failure locked the account
        logout();
        redirect('login.php');
    }
    $error = "That code didn't work. Enter the current code from your authenticator app.";
}

render('totp', ['title' => 'Enter your code', 'error' => $error]);
