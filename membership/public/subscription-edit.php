<?php
declare(strict_types=1);

// Add (?member=ID, who becomes the primary) or edit (?id=) a subscription:
// dates, payment, the people it covers, and each person's groups.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/members.php';
require dirname(__DIR__) . '/src/subscriptions.php';

$user = require_login();

$id = (int) ($_GET['id'] ?? 0);
$sub = $id ? find_subscription($id) : null;
if ($id && !$sub) {
    flash('That subscription was not found.');
    redirect('members.php');
}
$existingPeople = $sub ? subscription_people($id) : [];

if ($sub) {
    $primaryId = (int) (array_key_first(array_filter($existingPeople, fn($p) => $p['role'] === 'primary')) ?? array_key_first($existingPeople));
} else {
    $primaryId = (int) ($_GET['member'] ?? 0);
    if (!find_member($primaryId)) {
        flash('Choose a member first, then add a subscription from their page.');
        redirect('members.php');
    }
}
$back = 'member-edit.php?id=' . $primaryId . '#subscriptions';

// Choices: active ones, plus any inactive ones already on this subscription.
$stmt = db()->prepare('SELECT id, name, annual_fee FROM membership_types WHERE is_active = 1 OR id = ? ORDER BY name');
$stmt->execute([(int) ($sub['membership_type_id'] ?? 0)]);
$types = $stmt->fetchAll();

$stmt = db()->prepare('SELECT id, name FROM companies WHERE is_active = 1 OR id = ? ORDER BY name');
$stmt->execute([(int) ($sub['company_id'] ?? 0)]);
$companies = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT id, name FROM club_groups
      WHERE is_active = 1 OR id IN (SELECT group_id FROM subscription_member_groups WHERE subscription_id = ?)
      ORDER BY name'
);
$stmt->execute([$id]);
$groups = $stmt->fetchAll();

$members = db()->query('SELECT id, first_name, last_name, membership_number, status FROM members ORDER BY last_name, first_name')->fetchAll();

$errors = [];

if ($sub) {
    $values = array_map(fn($v) => (string) $v, $sub);
    $values['term_years'] = (string) term_for($sub['start_date'], $sub['expiry_date']);
    $values['people'] = array_map(fn($p) => [
        'member_id'         => (string) $p['member_id'],
        'role'              => $p['role'],
        'card_printed_date' => (string) $p['card_printed_date'],
        'groups'            => $p['groups'],
    ], array_values($existingPeople));
} else {
    $values = [
        'membership_type_id' => '', 'company_id' => '', 'term_years' => '1', 'expiry_date' => expiry_for_term(season_start(), 1),
        'start_date' => season_start(),
        'amount_paid' => '', 'payment_method' => '', 'paid_date' => '', 'payment_reference' => '',
        'student_details' => '', 'notes' => '',
        'people' => [['member_id' => (string) $primaryId, 'role' => 'primary', 'card_printed_date' => '', 'groups' => []]],
    ];
}

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete' && $sub) {
        if (empty($_POST['confirm_delete'])) {
            $errors[] = 'Tick the box to confirm you want to delete this subscription.';
        } else {
            subscription_delete($id);
            flash('Subscription deleted.');
            redirect($back);
        }
    } elseif ($action === 'save') {
        [$values, $data, $people, $errors] = subscription_validate(
            $_POST,
            $existingPeople,
            array_map(fn($t) => (int) $t['id'], $types),
            array_map(fn($c) => (int) $c['id'], $companies),
            array_map(fn($m) => (int) $m['id'], $members),
            array_map(fn($g) => (int) $g['id'], $groups),
        );
        if (!$errors) {
            subscription_save($id, $data, $people);
            $primaryId = (int) array_key_first(array_filter($people, fn($p) => $p['role'] === 'primary'));
            flash('Subscription saved.');
            redirect('member-edit.php?id=' . $primaryId . '#subscriptions');
        }
    }
}

$values['people'] = array_values(array_filter($values['people'], fn($p) => $p['member_id'] !== ''));
for ($i = 0; $i < BLANK_PERSON_ROWS; $i++) {
    $values['people'][] = blank_person_row();
}

render('subscription-edit', [
    'title'     => $sub ? 'Edit subscription' : 'Add subscription',
    'user'      => $user,
    'sub'       => $sub,
    'values'    => $values,
    'types'     => $types,
    'companies' => $companies,
    'groups'    => $groups,
    'members'   => $members,
    'back'      => $back,
    'primaryId' => $primaryId,
    'errors'    => $errors,
]);
