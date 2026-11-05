<?php
declare(strict_types=1);

// Add (no id) or edit (?id=) a member's details and interests.
// An existing member's page also lists their subscriptions and access items.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/members.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/access_items.php';

$user = require_login();

$id = (int) ($_GET['id'] ?? 0);
$member = $id ? find_member($id) : null;
if ($id && !$member) {
    flash('That member was not found.');
    redirect('members.php');
}

$values = $member ? array_map(fn($v) => (string) $v, $member) : member_defaults();
$selectedInterests = $member ? member_interest_ids($id) : [];
$errors = [];
$itemValues = access_item_defaults();
$itemErrors = [];

// Active interests, plus any inactive ones this member already has.
$stmt = db()->prepare(
    'SELECT id, name, is_active FROM interests
      WHERE is_active = 1 OR id IN (SELECT interest_id FROM member_interests WHERE member_id = ?)
      ORDER BY sort_order, name'
);
$stmt->execute([$id]);
$interests = $stmt->fetchAll();
$validInterestIds = array_map(fn($i) => (int) $i['id'], $interests);

$action = (string) ($_POST['action'] ?? '');

if (is_post() && $member && $action === 'issue_item') {
    csrf_check();
    [$itemValues, $itemData, $itemErrors] = access_item_validate($_POST, $id);
    if (!$itemErrors) {
        access_item_issue($id, $itemData);
        flash('Issued ' . mb_strtolower(ACCESS_ITEM_TYPES[$itemData['item_type']]) . '.');
        redirect('member-edit.php?id=' . $id . '#access');
    }
} elseif (is_post() && $member && $action === 'item_status') {
    csrf_check();
    $status = (string) ($_POST['status'] ?? '');
    if (access_item_set_status((int) ($_POST['item_id'] ?? 0), $id, $status)) {
        flash('Marked ' . mb_strtolower(ACCESS_ITEM_STATUSES[$status]) . '.');
    }
    redirect('member-edit.php?id=' . $id . '#access');
} elseif (is_post()) {
    csrf_check();
    [$values, $data, $errors] = member_validate($_POST, $id);
    $selectedInterests = array_values(array_intersect(
        array_map('intval', (array) ($_POST['interests'] ?? [])),
        $validInterestIds
    ));
    if (!$errors) {
        $id = member_save($id, $data, $selectedInterests);
        flash("Saved {$data['first_name']} {$data['last_name']}.");
        redirect('member-edit.php?id=' . $id);
    }
}

$name = $member ? trim($member['first_name'] . ' ' . $member['last_name']) : '';

render('member-edit', [
    'title'             => $member ? $name : 'Add member',
    'user'              => $user,
    'member'            => $member,
    'values'            => $values,
    'interests'         => $interests,
    'selectedInterests' => $selectedInterests,
    'statuses'          => MEMBER_STATUSES,
    'subscriptions'     => $member ? member_subscriptions($id) : [],
    'accessItems'       => $member ? member_access_items($id) : [],
    'bandsHeld'         => $member ? items_held($id, 'wristband') : 0,
    'keysHeld'          => $member ? array_filter(array_map(fn($t) => items_held($id, $t), array_combine(YEARLY_KEY_TYPES, YEARLY_KEY_TYPES))) : [],
    'itemValues'        => $itemValues,
    'itemErrors'        => $itemErrors,
    'today'             => date('Y-m-d'),
    'errors'            => $errors,
    'flash'             => take_flash(),
]);
