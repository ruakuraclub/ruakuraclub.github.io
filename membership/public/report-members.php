<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/members.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/reports.php';

$user = require_login();

$status = requested_member_status();
$companies = db()->query('SELECT id, name FROM companies ORDER BY is_active DESC, name')->fetchAll(PDO::FETCH_KEY_PAIR);
$companyId = (int) ($_GET['company'] ?? 0);
$companyId = isset($companies[$companyId]) ? $companyId : null;
$members = member_list($status, '', $companyId);

if ($format = requested_export_format()) {
    $headers = ['Membership no.', 'Surname', 'First name', 'Expires', 'Status', 'Company'];
    $rows = array_map(fn($m) => [
        $m['membership_number'] !== null ? (int) $m['membership_number'] : null,
        $m['last_name'], $m['first_name'], $m['expiry_date'], ucfirst($m['status']), $m['company_name'],
    ], $members);
    $slug = $companyId ? '-' . trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($companies[$companyId])), '-') : '';
    send_table($format, 'members-' . $status . $slug . '-' . date('Y-m-d'), 'Members', $headers, $rows);
}

render('report-members', [
    'title'     => 'Members report',
    'user'      => $user,
    'status'    => $status,
    'statuses'  => MEMBER_STATUSES,
    'companies' => $companies,
    'companyId' => $companyId,
    'members'   => $members,
    'today'     => date('Y-m-d'),
]);
