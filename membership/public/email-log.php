<?php
declare(strict_types=1);

// Emails sent from Email members: a list of sends, or one send's recipients (?id=).

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/brevo.php';
require dirname(__DIR__) . '/src/email.php';

const EMAIL_LOG_LIMIT = 200;

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);
$send = $id ? find_email_send($id) : null;
if ($id && !$send) {
    flash('That email was not found.');
    redirect('email-log.php');
}

render('email-log', [
    'title'      => $send ? 'Sent email' : 'Sent emails',
    'user'       => $user,
    'send'       => $send,
    'recipients' => $send ? email_send_recipients($id) : [],
    'sends'      => $send ? [] : email_sends(EMAIL_LOG_LIMIT),
    'limit'      => EMAIL_LOG_LIMIT,
    'flash'      => take_flash(),
]);
