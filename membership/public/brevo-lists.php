<?php
declare(strict_types=1);

// Choose which Brevo lists members go on: a main list, a lapsed list, and one per interest.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/brevo.php';

$user = require_login();
$errors = [];
$lists = [];
$mapping = brevo_mapping();
$interests = db()->query('SELECT id, name, brevo_list_id FROM interests WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();

if (brevo_configured()) {
    try {
        $lists = brevo_lists();
    } catch (BrevoError $e) {
        $errors[] = $e->getMessage();
    }
}

// Saved lists Brevo didn't return (deleted there, or Brevo unreachable) stay choosable so nothing is lost by accident.
$choices = $lists;
foreach (brevo_managed_lists($mapping) as $id) {
    $choices[$id] ??= "List #{$id} (not found in Brevo)";
}

$values = [
    'main'      => (string) ($mapping['main'] ?? ''),
    'lapsed'    => (string) ($mapping['lapsed'] ?? ''),
    'interests' => array_column(array_map(fn($i) => [$i['id'], (string) $i['brevo_list_id']], $interests), 1, 0),
];

if (is_post() && brevo_configured()) {
    csrf_check();
    $pick = function (string $value) use ($choices, &$errors): ?int {
        if ($value === '') {
            return null;
        }
        if (!isset($choices[(int) $value])) {
            $errors[] = 'One of the chosen lists is no longer in Brevo. Reload the page and choose again.';
            return null;
        }
        return (int) $value;
    };
    $values['main'] = (string) ($_POST['main'] ?? '');
    $values['lapsed'] = (string) ($_POST['lapsed'] ?? '');
    $main = $pick($values['main']);
    $lapsed = $pick($values['lapsed']);
    $interestLists = [];
    foreach ($interests as $i) {
        $values['interests'][$i['id']] = (string) ($_POST['interests'][$i['id']] ?? '');
        $interestLists[(int) $i['id']] = $pick($values['interests'][$i['id']]);
    }

    if ($lapsed !== null && ($lapsed === $main || in_array($lapsed, $interestLists, true))) {
        $errors[] = 'The lapsed members list must be a list of its own, not the main list or an interest list.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        set_setting('brevo_main_list_id', $main === null ? null : (string) $main);
        set_setting('brevo_lapsed_list_id', $lapsed === null ? null : (string) $lapsed);
        $stmt = $pdo->prepare('UPDATE interests SET brevo_list_id = ? WHERE id = ?');
        foreach ($interestLists as $id => $listId) {
            $stmt->execute([$listId, $id]);
        }
        $pdo->commit();
        flash('Brevo lists saved.');
        redirect('brevo-lists.php');
    }
}

render('brevo-lists', [
    'title'      => 'Brevo lists',
    'user'       => $user,
    'configured' => brevo_configured(),
    'reachable'  => (bool) $lists,
    'choices'    => $choices,
    'interests'  => $interests,
    'values'     => $values,
    'errors'     => $errors,
    'flash'      => take_flash(),
]);
