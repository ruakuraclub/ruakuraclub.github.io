<?php
declare(strict_types=1);

// Correct a card, key or wristband record: fix details, change the status
// (including back to Active), or delete one entered by mistake.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/members.php';
require dirname(__DIR__) . '/src/access_items.php';

$user = require_login();

$item = find_access_item((int) ($_GET['id'] ?? 0));
if (!$item) {
    flash('That item was not found.');
    redirect('members.php');
}
$member = find_member((int) $item['member_id']);
$back = 'member-edit.php?id=' . (int) $item['member_id'] . '#access';

$values = array_map(fn($v) => (string) $v, $item);
$errors = [];

if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        if (empty($_POST['confirm_delete'])) {
            $errors[] = 'Tick the box to confirm you want to delete this record.';
        } else {
            access_item_delete((int) $item['id']);
            flash('Deleted the ' . mb_strtolower(ACCESS_ITEM_TYPES[$item['item_type']]) . ' record.');
            redirect($back);
        }
    } else {
        [$values, $data, $errors] = access_item_edit_validate($item, $_POST);
        if (!$errors) {
            access_item_update((int) $item['id'], $data);
            flash('Saved the ' . mb_strtolower(ACCESS_ITEM_TYPES[$item['item_type']]) . ' record.');
            redirect($back);
        }
    }
}

render('access-item-edit', [
    'title'  => 'Edit ' . mb_strtolower(ACCESS_ITEM_TYPES[$item['item_type']]),
    'user'   => $user,
    'item'   => $item,
    'member' => $member,
    'values' => $values,
    'back'   => $back,
    'errors' => $errors,
]);
