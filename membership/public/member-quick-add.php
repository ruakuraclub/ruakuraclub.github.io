<?php
declare(strict_types=1);

// The "New member" dialog on the subscription form.
// GET  -> {"next": N}  the next free membership number
// POST -> {"id": N, "label": "..."} or {"errors": [...]}  adds an active member (same checks as the member form)

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/members.php';

require_login();
header('Content-Type: application/json');

if (!is_post()) {
    echo json_encode(['next' => next_membership_number()]);
    exit;
}

csrf_check();
[, $data, $errors] = member_validate(['status' => 'active'] + $_POST, 0);
if ($errors) {
    echo json_encode(['errors' => $errors]);
    exit;
}
$id = member_save(0, $data, []);
echo json_encode([
    'id'    => $id,
    'label' => $data['last_name'] . ', ' . $data['first_name'] . ($data['membership_number'] !== null ? ' (' . $data['membership_number'] . ')' : ''),
]);
