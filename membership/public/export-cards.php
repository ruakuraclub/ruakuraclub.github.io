<?php
declare(strict_types=1);

// Membership cards: download RuakuraClub.xlsx for the card designer, then
// record which cards were printed.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/cards.php';

$user = require_login();
$today = date('Y-m-d');

const CARD_FILTERS = ['unprinted' => 'Not printed yet', 'all' => 'All current members'];

$filter = (string) ($_POST['filter'] ?? $_GET['filter'] ?? 'unprinted');
if (!isset(CARD_FILTERS[$filter])) {
    $filter = 'unprinted';
}
$candidates = current_subscription_people($today, $filter === 'unprinted' ? 'card_printed_date' : null);
$errors = [];

if (is_post()) {
    csrf_check();
    $selected = ticked_people($candidates, (array) ($_POST['cards'] ?? []));
    $action = (string) ($_POST['action'] ?? '');
    if (!$selected) {
        $errors[] = 'Tick at least one member.';
    } elseif ($action === 'download') {
        send_card_xlsx($selected);
    } elseif ($action === 'mark') {
        $count = mark_people_sent($selected, 'card_printed_date', $today);
        flash($count === 1 ? 'Marked 1 card as printed.' : "Marked {$count} cards as printed.");
        redirect('export-cards.php?filter=' . urlencode($filter));
    }
}

render('export-cards', [
    'title'      => 'Print cards',
    'user'       => $user,
    'filter'     => $filter,
    'candidates' => $candidates,
    'ticked'     => is_post() ? array_flip(array_map('strval', (array) ($_POST['cards'] ?? []))) : null,
    'errors'     => $errors,
    'flash'      => take_flash(),
]);
