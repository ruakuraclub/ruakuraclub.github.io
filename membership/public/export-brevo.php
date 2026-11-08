<?php
declare(strict_types=1);

// Brings the chosen Brevo lists up to date with members: shows what would change, then sends it.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/brevo.php';

$user = require_login();
$today = date('Y-m-d');
$plan = null;
$errors = [];

if (brevo_configured()) {
    try {
        $plan = brevo_plan($today);
        if (is_post()) {
            csrf_check();
            [$imported, $removed] = brevo_sync($plan);
            flash("Sent to Brevo: {$imported} contact" . ($imported === 1 ? '' : 's') . " updated on their lists, {$removed} list removal"
                . ($removed === 1 ? '' : 's') . '. Brevo adds new contacts in the background, so allow a few minutes before checking.');
            redirect('export-brevo.php');
        }
    } catch (BrevoError $e) {
        $errors[] = $e->getMessage();
    }
}

render('export-brevo', [
    'title'      => 'Update Brevo mailing list',
    'user'       => $user,
    'configured' => brevo_configured(),
    'plan'       => $plan,
    'interestNames' => db()->query('SELECT id, name FROM interests')->fetchAll(PDO::FETCH_KEY_PAIR),
    'lastSync'   => brevo_configured() ? setting('brevo_last_sync') : null,
    'errors'     => $errors,
    'flash'      => take_flash(),
]);
