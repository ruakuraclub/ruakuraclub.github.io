<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/access_items.php';
require dirname(__DIR__) . '/src/reports.php';

$user = require_login();

$status = (string) ($_GET['status'] ?? 'active');
if ($status !== 'all' && !isset(ACCESS_ITEM_STATUSES[$status])) {
    $status = 'active';
}
$cards = barrier_card_report($status);

if ($format = requested_export_format()) {
    [$headers, $rows] = barrier_card_export($cards);
    send_table($format, 'barrier-cards-' . $status . '-' . date('Y-m-d'), 'Barrier cards', $headers, $rows);
}

render('report-barrier-cards', [
    'title'  => 'Barrier cards',
    'user'   => $user,
    'status' => $status,
    'cards'  => $cards,
]);
