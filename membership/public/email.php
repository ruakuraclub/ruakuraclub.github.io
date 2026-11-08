<?php
declare(strict_types=1);

// Email members: choose a Brevo template and who gets it, send a test, then send.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/brevo.php';
require dirname(__DIR__) . '/src/email.php';

$user = require_login();
$today = date('Y-m-d');
$errors = [];
$templates = [];

// The filter comes from the address, or from the send form's hidden copy of it.
$who = (string) ($_POST['who'] ?? $_GET['who'] ?? 'current');
if (!isset(EMAIL_WHO[$who])) {
    $who = 'current';
}
$expiresBy = clean_date((string) ($_POST['expires_by'] ?? $_GET['expires_by'] ?? '')) ?? '';
$candidates = email_candidates($who, $expiresBy, $today);

$values = [
    'template_id' => (string) ($_POST['template_id'] ?? ''),
    'mode'        => isset(EMAIL_MODES[$_POST['mode'] ?? '']) ? $_POST['mode'] : 'individual',
    'test_email'  => (string) ($_POST['test_email'] ?? (brevo_configured() ? setting('email_test_address') : '') ?? ''),
];
$ticked = is_post() ? array_flip(array_map('strval', (array) ($_POST['members'] ?? []))) : null;

if (brevo_configured()) {
    try {
        $templates = brevo_templates();
    } catch (BrevoError $e) {
        $errors[] = $e->getMessage();
    }
}

if (is_post() && $templates) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $templateId = (int) $values['template_id'];
    $chosen = array_values(array_filter($candidates, fn($m) => isset($ticked[(string) $m['id']])));
    $recipients = email_recipients($chosen);

    if (!isset($templates[$templateId])) {
        $errors[] = 'Choose a template.';
    } elseif ($action === 'test') {
        $testEmail = trim($values['test_email']);
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter the email address to send the test to.';
        } else {
            set_setting('email_test_address', $testEmail);
            // Filled in with the first ticked recipient's details, so placeholders can be checked.
            $sample = $recipients ? reset($recipients) : ['params' => []];
            $test = ['email' => $testEmail, 'name' => $testEmail, 'member_ids' => [], 'params' => $sample['params']];
            try {
                email_send($templates[$templateId], $templateId, [$test], 'test', (int) $user['id']);
                flash("Test sent to {$testEmail}" . ($recipients ? ', filled in with ' . reset($recipients)['name'] . "'s details." : '.'));
                $values['test_email'] = $testEmail;
            } catch (BrevoError $e) {
                $errors[] = $e->getMessage();
            }
        }
    } elseif ($action === 'send') {
        $cost = email_cost($recipients, $values['mode']);
        $sentToday = emails_sent_today();
        if (!$recipients) {
            $errors[] = 'Tick at least one member with an email address.';
        } elseif (empty($_POST['confirm_send'])) {
            $errors[] = 'Tick the box to confirm you want to send it.';
        } elseif ($values['mode'] === 'bcc' && $templates[$templateId]['sender_email'] === '') {
            $errors[] = "This template has no sender address, which a BCC email is addressed to. Set one in Brevo.";
        } elseif ($sentToday + $cost > email_daily_limit()) {
            $errors[] = "This needs {$cost} emails, but only " . max(0, email_daily_limit() - $sentToday) . ' of today\'s ' . email_daily_limit() . ' are left. Send the rest tomorrow, or untick some members.';
        } else {
            try {
                $sendId = email_send($templates[$templateId], $templateId, array_values($recipients), $values['mode'], (int) $user['id']);
                flash('Sent "' . $templates[$templateId]['name'] . '" to ' . count($recipients) . ' email address' . (count($recipients) === 1 ? '' : 'es') . '.');
                redirect('email-log.php?id=' . $sendId);
            } catch (BrevoError $e) {
                $errors[] = $e->getMessage() . ' Check Email → Sent emails to see whether any went before the error.';
            }
        }
    }
}

render('email', [
    'title'      => 'Email members',
    'user'       => $user,
    'configured' => brevo_configured(),
    'templates'  => $templates,
    'who'        => $who,
    'expiresBy'  => $expiresBy,
    'candidates' => $candidates,
    'ticked'     => $ticked,
    'values'     => $values,
    'sentToday'  => brevo_configured() ? emails_sent_today() : 0,
    'limit'      => email_daily_limit(),
    'errors'     => $errors,
    'flash'      => take_flash(),
]);
