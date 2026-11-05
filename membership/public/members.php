<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/members.php';

$user = require_login();

$q = trim((string) ($_GET['q'] ?? ''));
$status = requested_member_status();

render('members', [
    'title'    => 'Members',
    'user'     => $user,
    'members'  => member_list($status, $q),
    'q'        => $q,
    'status'   => $status,
    'statuses' => MEMBER_STATUSES,
    'today'    => date('Y-m-d'),
    'flash'    => take_flash(),
]);
