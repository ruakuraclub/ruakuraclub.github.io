<?php
declare(strict_types=1);

// Menumate patrons: download Members.csv to import, then record who was sent.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/cards.php';
require dirname(__DIR__) . '/src/menumate.php';

$user = require_login();
$today = date('Y-m-d');

const MENUMATE_FILTERS = ['unsent' => 'Not sent to Menumate yet', 'all' => 'All current members'];

$filter = (string) ($_POST['filter'] ?? $_GET['filter'] ?? 'unsent');
if (!isset(MENUMATE_FILTERS[$filter])) {
    $filter = 'unsent';
}
$candidates = current_subscription_people($today, $filter === 'unsent' ? 'menumate_updated_date' : null);
$errors = [];

if (is_post()) {
    csrf_check();
    $selected = ticked_people($candidates, (array) ($_POST['people'] ?? []));
    $action = (string) ($_POST['action'] ?? '');
    if (!$selected) {
        $errors[] = 'Tick at least one member.';
    } elseif ($action === 'download') {
        send_menumate_csv($selected);
    } elseif ($action === 'mark') {
        $count = mark_people_sent($selected, 'menumate_updated_date', $today);
        flash($count === 1 ? 'Marked 1 member as sent to Menumate.' : "Marked {$count} members as sent to Menumate.");
        redirect('export-menumate.php?filter=' . urlencode($filter));
    }
}

render('export-menumate', [
    'title'      => 'Update Menumate',
    'user'       => $user,
    'filter'     => $filter,
    'candidates' => $candidates,
    'ticked'     => is_post() ? array_flip(array_map('strval', (array) ($_POST['people'] ?? []))) : null,
    'errors'     => $errors,
    'flash'      => take_flash(),
]);
