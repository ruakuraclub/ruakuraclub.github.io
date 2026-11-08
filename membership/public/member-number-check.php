<?php
declare(strict_types=1);

// Live check for the member form: is this membership number free?
// ?number=N&except=ID -> {"status": "available"} | {"status": "taken", "name": "...", "id": N} | {"status": "invalid"}
// The real check still happens when the form is saved.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/members.php';

require_login();
header('Content-Type: application/json');

$number = trim((string) ($_GET['number'] ?? ''));
if (!ctype_digit($number) || strlen($number) > 9) {
    echo json_encode(['status' => 'invalid']);
    exit;
}
$other = member_with_number((int) $number, (int) ($_GET['except'] ?? 0));
echo json_encode($other
    ? ['status' => 'taken', 'name' => trim($other['first_name'] . ' ' . $other['last_name']), 'id' => (int) $other['id']]
    : ['status' => 'available']);
