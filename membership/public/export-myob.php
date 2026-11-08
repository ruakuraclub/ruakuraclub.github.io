<?php
declare(strict_types=1);

// MYOB contacts: download the payers of current subscriptions as a MYOB import file.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/myob.php';

$user = require_login();
$today = date('Y-m-d');

$filter = (string) ($_POST['filter'] ?? $_GET['filter'] ?? 'active');
if (!isset(MYOB_FILTERS[$filter])) {
    $filter = 'active';
}
$contacts = myob_contacts($filter, $today);
$errors = [];

if (is_post()) {
    csrf_check();
    $keys = array_flip(array_map('strval', (array) ($_POST['contacts'] ?? [])));
    $selected = array_values(array_filter($contacts, fn($c) => isset($keys[$c['key']])));
    if (!$selected) {
        $errors[] = 'Tick at least one contact.';
    } else {
        send_myob_contacts($selected);
    }
}

render('export-myob', [
    'title'    => 'MYOB contacts',
    'user'     => $user,
    'filter'   => $filter,
    'contacts' => $contacts,
    'ticked'   => is_post() ? array_flip(array_map('strval', (array) ($_POST['contacts'] ?? []))) : null,
    'errors'   => $errors,
]);
