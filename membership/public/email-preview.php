<?php
declare(strict_types=1);

// Preview of a Brevo template for one recipient, shown in a frame on Email members.
// ?template=ID&members=1,2&mode=individual|bcc            -> the email's HTML
// ...&format=json                                          -> {to, subject} (or {error})
// The HTML is the template author's, so it's shown with scripts off and nothing but
// styles, images and fonts allowed to load.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/brevo.php';
require dirname(__DIR__) . '/src/email.php';

require_login();

$json = ($_GET['format'] ?? '') === 'json';
$fail = function (string $message) use ($json): never {
    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
    }
    exit;
};

if (!brevo_configured()) {
    $fail("Brevo isn't set up.");
}
try {
    $template = brevo_template((int) ($_GET['template'] ?? 0));
} catch (BrevoError $e) {
    $fail($e->getMessage());
}

$bcc = ($_GET['mode'] ?? '') === 'bcc';
$ids = array_flip(array_filter(array_map('intval', explode(',', (string) ($_GET['members'] ?? '')))));
$members = array_filter(email_candidates('all', '', date('Y-m-d')), fn($m) => isset($ids[(int) $m['id']]));
$recipients = email_recipients($members);
$recipient = $recipients ? reset($recipients) : null;
$params = ($bcc || !$recipient) ? [] : $recipient['params'];

if ($json) {
    header('Content-Type: application/json');
    echo json_encode([
        'to'      => $bcc
            ? trim($template['sender_name'] . ' <' . $template['sender_email'] . '>') . ' (members in BCC)'
            : ($recipient ? $recipient['name'] . ' <' . $recipient['email'] . '>' : ''),
        'subject' => email_fill($template['subject'], $params, false),
    ]);
    exit;
}

header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline' https:; img-src https: data:; font-src https: data:; frame-ancestors 'self'; sandbox", true);
header('Content-Type: text/html; charset=utf-8');
echo email_fill($template['html'], $params, true);
