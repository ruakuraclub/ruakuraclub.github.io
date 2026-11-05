<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/members.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/reports.php';

$user = require_login();

$status = requested_member_status();
$members = member_list($status);

if ($format = requested_export_format()) {
    $headers = ['Membership no.', 'Surname', 'First name', 'Expires', 'Status'];
    $rows = array_map(fn($m) => [
        $m['membership_number'] !== null ? (int) $m['membership_number'] : null,
        $m['last_name'], $m['first_name'], $m['expiry_date'], ucfirst($m['status']),
    ], $members);
    send_table($format, 'members-' . $status . '-' . date('Y-m-d'), 'Members', $headers, $rows);
}

render('report-members', [
    'title'    => 'Members report',
    'user'     => $user,
    'status'   => $status,
    'statuses' => MEMBER_STATUSES,
    'members'  => $members,
    'today'    => date('Y-m-d'),
]);
