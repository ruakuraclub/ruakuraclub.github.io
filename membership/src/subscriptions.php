<?php
declare(strict_types=1);

const PAYMENT_METHODS = ['eftpos' => 'EFTPOS', 'internet' => 'Internet banking', 'cash' => 'Cash', 'other' => 'Other'];
const SUBSCRIPTION_ROLES = ['primary' => 'Primary', 'partner' => 'Partner', 'junior' => 'Junior'];

function find_subscription(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM subscriptions WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** People on a subscription, keyed by member id, each with a 'groups' list of group ids. */
function subscription_people(int $subscriptionId): array
{
    $stmt = db()->prepare(
        "SELECT sm.*, m.first_name, m.last_name, m.membership_number, m.status
           FROM subscription_members sm
           JOIN members m ON m.id = sm.member_id
          WHERE sm.subscription_id = ?
          ORDER BY CASE sm.role WHEN 'primary' THEN 1 WHEN 'partner' THEN 2 ELSE 3 END, m.last_name, m.first_name"
    );
    $stmt->execute([$subscriptionId]);
    $people = [];
    foreach ($stmt->fetchAll() as $row) {
        $people[(int) $row['member_id']] = $row + ['groups' => []];
    }
    $stmt = db()->prepare('SELECT member_id, group_id FROM subscription_member_groups WHERE subscription_id = ?');
    $stmt->execute([$subscriptionId]);
    foreach ($stmt->fetchAll() as $row) {
        $people[(int) $row['member_id']]['groups'][] = (int) $row['group_id'];
    }
    return $people;
}

/** A member's subscriptions, newest first, for the member page. */
function member_subscriptions(int $memberId): array
{
    $stmt = db()->prepare(
        'SELECT s.*, t.name AS type_name, sm.role
           FROM subscription_members sm
           JOIN subscriptions s ON s.id = sm.subscription_id
           JOIN membership_types t ON t.id = s.membership_type_id
          WHERE sm.member_id = ?
          ORDER BY s.expiry_date DESC'
    );
    $stmt->execute([$memberId]);
    $subs = $stmt->fetchAll();

    $others = db()->prepare(
        'SELECT m.first_name, m.last_name, sm.role
           FROM subscription_members sm JOIN members m ON m.id = sm.member_id
          WHERE sm.subscription_id = ? AND sm.member_id <> ?
          ORDER BY m.last_name, m.first_name'
    );
    $groups = db()->prepare(
        'SELECT g.name FROM subscription_member_groups smg JOIN club_groups g ON g.id = smg.group_id
          WHERE smg.subscription_id = ? AND smg.member_id = ? ORDER BY g.name'
    );
    foreach ($subs as &$sub) {
        $others->execute([$sub['id'], $memberId]);
        $sub['others'] = $others->fetchAll();
        $groups->execute([$sub['id'], $memberId]);
        $sub['groups'] = $groups->fetchAll(PDO::FETCH_COLUMN);
    }
    return $subs;
}

/** 1 November of this year, or of last year from January to August. */
function season_start(?string $today = null): string
{
    $today = new DateTime($today ?? 'today');
    $year = (int) $today->format('Y') - ((int) $today->format('n') <= 8 ? 1 : 0);
    return "{$year}-11-01";
}

/** Start date + whole years, less one day: 2026-11-01 + 1 year = 2027-10-31. */
function expiry_for_term(string $start, int $years): string
{
    return (new DateTime($start))->modify("+{$years} years -1 day")->format('Y-m-d');
}

/** The term (in years) for an existing subscription: exact if it matches, otherwise the nearest whole year. */
function term_for(string $start, string $expiry): int
{
    for ($years = 1; $years <= 10; $years++) {
        if (expiry_for_term($start, $years) === $expiry) {
            return $years;
        }
    }
    $days = (new DateTime($start))->diff(new DateTime($expiry))->days;
    return max(1, min(10, (int) round($days / 365.25)));
}

function blank_person_row(string $role = ''): array
{
    return ['member_id' => '', 'role' => $role, 'card_printed_date' => '', 'groups' => []];
}

/**
 * Checks the posted form.
 * Returns [values to redisplay, data for `subscriptions`, people to save (member_id => row), errors].
 */
function subscription_validate(array $input, array $existingPeople, array $validTypeIds, array $validCompanyIds, array $validMemberIds, array $validGroupIds): array
{
    $text = fn(string $k) => trim((string) ($input[$k] ?? ''));
    $values = [];
    foreach (['membership_type_id', 'company_id', 'start_date', 'term_years', 'expiry_date', 'amount_paid', 'payment_method',
              'paid_date', 'payment_reference', 'student_details', 'notes'] as $k) {
        $values[$k] = $text($k);
    }
    $data = [];
    $errors = [];

    $data['membership_type_id'] = (int) $values['membership_type_id'];
    if (!in_array($data['membership_type_id'], $validTypeIds, true)) {
        $errors[] = 'Choose a membership type.';
    }

    $data['company_id'] = $values['company_id'] === '' ? null : (int) $values['company_id'];
    if ($data['company_id'] !== null && !in_array($data['company_id'], $validCompanyIds, true)) {
        $errors[] = 'Choose a company from the list.';
    }

    $start = clean_date($values['start_date']);
    if (!$start) {
        $errors[] = 'Enter a valid start date.';
    }
    $data['start_date'] = $start ?: null;

    $years = ctype_digit($values['term_years']) ? (int) $values['term_years'] : 0;
    if ($years < 1 || $years > 10) {
        $errors[] = 'Enter a term of 1–10 years.';
    } elseif ($values['expiry_date'] === '' && $start) {
        $values['expiry_date'] = expiry_for_term($start, $years);   // otherwise the entered date overrides
    }
    $expiry = clean_date($values['expiry_date']);
    if (!$expiry) {
        $errors[] = 'Enter a valid expiry date.';
    } elseif ($start && $expiry <= $start) {
        $errors[] = 'The expiry date must be after the start date.';
    }
    $data['expiry_date'] = $expiry ?: null;

    $data['amount_paid'] = null;
    if ($values['amount_paid'] !== '') {
        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $values['amount_paid'])) {
            $errors[] = 'Amount paid must be an amount like 85 or 85.00.';
        } else {
            $data['amount_paid'] = $values['amount_paid'];
        }
    }
    $data['payment_method'] = $values['payment_method'] === '' ? null : $values['payment_method'];
    if ($data['payment_method'] !== null && !isset(PAYMENT_METHODS[$data['payment_method']])) {
        $errors[] = 'Choose a payment method.';
    }
    $paid = clean_date($values['paid_date']);
    if ($paid === null) {
        $errors[] = "Paid date isn't a valid date.";
    }
    $data['paid_date'] = $paid ?: null;

    foreach (['payment_reference' => 100, 'student_details' => 150] as $k => $max) {
        if (mb_strlen($values[$k]) > $max) {
            $errors[] = ucfirst(str_replace('_', ' ', $k)) . " must be at most {$max} characters.";
        }
        $data[$k] = $values[$k] === '' ? null : $values[$k];
    }
    $data['notes'] = $values['notes'] === '' ? null : $values['notes'];

    // People
    $rows = [];
    $people = [];
    $primaries = 0;
    foreach ((array) ($input['people'] ?? []) as $p) {
        $row = [
            'member_id'         => trim((string) ($p['member_id'] ?? '')),
            'role'              => (string) ($p['role'] ?? ''),
            'card_printed_date' => trim((string) ($p['card_printed_date'] ?? '')),
            'groups'            => array_values(array_intersect(array_map('intval', (array) ($p['groups'] ?? [])), $validGroupIds)),
        ];
        $rows[] = $row;
        if ($row['member_id'] === '') {
            continue;
        }
        $memberId = (int) $row['member_id'];
        if (!in_array($memberId, $validMemberIds, true)) {
            $errors[] = 'One of the people chosen no longer exists.';
            continue;
        }
        if (isset($people[$memberId])) {
            $errors[] = 'The same person is listed twice.';
            continue;
        }
        if (!isset(SUBSCRIPTION_ROLES[$row['role']])) {
            $errors[] = 'Choose a role for each person.';
            continue;
        }
        $printed = clean_date($row['card_printed_date']);
        if ($printed === null) {
            $errors[] = "A card printed date isn't valid.";
        }
        $primaries += $row['role'] === 'primary' ? 1 : 0;
        $people[$memberId] = ['role' => $row['role'], 'card_printed_date' => $printed ?: null, 'groups' => $row['groups']];
    }
    if ($primaries !== 1) {
        $errors[] = 'Choose exactly one primary member.';
    }

    // Groups can only be added while the subscription is current (they end when it does).
    if ($expiry && $expiry < date('Y-m-d')) {
        foreach ($people as $memberId => $p) {
            if (array_diff($p['groups'], $existingPeople[$memberId]['groups'] ?? [])) {
                $errors[] = "Sub & affiliate clubs can't be added to a subscription that has already expired.";
                break;
            }
        }
    }

    $values['people'] = $rows;
    return [$values, $data, $people, $errors];
}

/** Inserts or updates a subscription and who it covers. Returns its id. */
function subscription_save(int $id, array $data, array $people): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $columns = array_keys($data);
        if ($id) {
            $set = implode(', ', array_map(fn($c) => "{$c} = ?", $columns));
            $pdo->prepare("UPDATE subscriptions SET {$set} WHERE id = ?")->execute([...array_values($data), $id]);
        } else {
            $marks = implode(', ', array_fill(0, count($columns), '?'));
            $pdo->prepare('INSERT INTO subscriptions (' . implode(', ', $columns) . ") VALUES ({$marks})")
                ->execute(array_values($data));
            $id = (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare('SELECT member_id FROM subscription_members WHERE subscription_id = ?');
        $stmt->execute([$id]);
        $existing = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        foreach (array_diff($existing, array_keys($people)) as $memberId) {
            $pdo->prepare('DELETE FROM subscription_member_groups WHERE subscription_id = ? AND member_id = ?')->execute([$id, $memberId]);
            $pdo->prepare('DELETE FROM subscription_members WHERE subscription_id = ? AND member_id = ?')->execute([$id, $memberId]);
        }

        $currentGroups = $pdo->prepare('SELECT group_id FROM subscription_member_groups WHERE subscription_id = ? AND member_id = ?');
        $removeGroup = $pdo->prepare('DELETE FROM subscription_member_groups WHERE subscription_id = ? AND member_id = ? AND group_id = ?');
        $addGroup = $pdo->prepare('INSERT INTO subscription_member_groups (subscription_id, member_id, group_id) VALUES (?, ?, ?)');

        foreach ($people as $memberId => $p) {
            if (in_array($memberId, $existing, true)) {
                $pdo->prepare('UPDATE subscription_members SET role = ?, card_printed_date = ? WHERE subscription_id = ? AND member_id = ?')
                    ->execute([$p['role'], $p['card_printed_date'], $id, $memberId]);
            } else {
                $pdo->prepare('INSERT INTO subscription_members (subscription_id, member_id, role, card_printed_date) VALUES (?, ?, ?, ?)')
                    ->execute([$id, $memberId, $p['role'], $p['card_printed_date']]);
            }
            // Only touch groups that changed, so any details stored on kept ones survive.
            $currentGroups->execute([$id, $memberId]);
            $have = array_map('intval', $currentGroups->fetchAll(PDO::FETCH_COLUMN));
            foreach (array_diff($have, $p['groups']) as $groupId) {
                $removeGroup->execute([$id, $memberId, $groupId]);
            }
            foreach (array_diff($p['groups'], $have) as $groupId) {
                $addGroup->execute([$id, $memberId, $groupId]);
            }
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return $id;
}

function subscription_delete(int $id): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM subscription_member_groups WHERE subscription_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM subscription_members WHERE subscription_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM subscriptions WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/** "2026/27" for the season starting 2026-11-01. */
function season_label(string $seasonStart): string
{
    $year = (int) substr($seasonStart, 0, 4);
    return $year . '/' . substr((string) ($year + 1), 2);
}

/**
 * Life members who need a subscription for the season: active members whose most
 * recent subscription is a life type (as the primary), with nothing yet covering the season start.
 */
function life_renewals_due(string $seasonStart): array
{
    $stmt = db()->prepare(
        "SELECT s.id AS subscription_id, s.membership_type_id, s.expiry_date, t.name AS type_name,
                m.id AS member_id, m.first_name, m.last_name, m.membership_number
           FROM subscriptions s
           JOIN subscription_members sm ON sm.subscription_id = s.id AND sm.role = 'primary'
           JOIN members m ON m.id = sm.member_id
           JOIN membership_types t ON t.id = s.membership_type_id
          WHERE t.is_life = 1
            AND m.status = 'active'
            AND s.expiry_date = (SELECT MAX(s2.expiry_date)
                                   FROM subscriptions s2
                                   JOIN subscription_members sm2 ON sm2.subscription_id = s2.id
                                  WHERE sm2.member_id = m.id)
            AND NOT EXISTS (SELECT 1
                              FROM subscriptions s3
                              JOIN subscription_members sm3 ON sm3.subscription_id = s3.id
                             WHERE sm3.member_id = m.id AND s3.start_date <= ? AND s3.expiry_date >= ?)
          ORDER BY m.last_name, m.first_name"
    );
    $stmt->execute([$seasonStart, $seasonStart]);
    return $stmt->fetchAll();
}

/**
 * Creates a $0, one-year subscription for each life member due, copying the people
 * (and roles) from their last one, except anyone no longer active. Groups aren't copied:
 * they're per season. Returns how many were created.
 */
function renew_life_members(string $seasonStart): int
{
    $due = life_renewals_due($seasonStart);
    foreach ($due as $row) {
        $people = [];
        foreach (subscription_people((int) $row['subscription_id']) as $memberId => $p) {
            if ($p['status'] === 'active') {
                // Life members keep their card, so no new one is printed.
                $people[$memberId] = ['role' => $p['role'], 'card_printed_date' => date('Y-m-d'), 'groups' => []];
            }
        }
        subscription_save(0, [
            'membership_type_id' => (int) $row['membership_type_id'],
            'start_date'         => $seasonStart,
            'expiry_date'        => expiry_for_term($seasonStart, 1),
            'amount_paid'        => '0',
            'payment_method'     => null,
            'paid_date'          => date('Y-m-d'),
            'payment_reference'  => null,
            'student_details'    => null,
            'notes'              => 'Life member – renewed automatically',
        ], $people);
    }
    return count($due);
}

function format_date(?string $date): string
{
    return $date ? date('j M Y', strtotime($date)) : '';
}

// Per-person dates recording when they were last sent to an outside system.
const SENT_DATE_COLUMNS = ['card_printed_date', 'menumate_updated_date'];

/**
 * Active members on a subscription that hasn't expired, one row per person
 * (their latest-expiring subscription), sorted by surname.
 * $notSentColumn limits it to people whose date in that column is still empty.
 */
function current_subscription_people(string $today, ?string $notSentColumn = null): array
{
    $sql = "SELECT sm.subscription_id, sm.member_id, sm.role,
                   sm.card_printed_date, sm.menumate_updated_date,
                   s.expiry_date, s.paid_date, s.company_id, t.is_life, c.name AS company_name,
                   m.membership_number, m.first_name, m.last_name, m.preferred_name, m.email, m.phone, m.phone_work, m.address, m.date_of_birth
              FROM subscription_members sm
              JOIN subscriptions s ON s.id = sm.subscription_id
              JOIN membership_types t ON t.id = s.membership_type_id
              LEFT JOIN companies c ON c.id = s.company_id
              JOIN members m ON m.id = sm.member_id
             WHERE m.status = 'active'
               AND s.expiry_date >= ?
               AND s.id = (SELECT s2.id
                             FROM subscription_members sm2
                             JOIN subscriptions s2 ON s2.id = sm2.subscription_id
                            WHERE sm2.member_id = m.id
                            ORDER BY s2.expiry_date DESC, s2.id DESC
                            LIMIT 1)";
    if ($notSentColumn !== null) {
        if (!in_array($notSentColumn, SENT_DATE_COLUMNS, true)) {
            throw new InvalidArgumentException("Unknown column {$notSentColumn}");
        }
        $sql .= " AND sm.{$notSentColumn} IS NULL";
    }
    $stmt = db()->prepare($sql . ' ORDER BY m.last_name, m.first_name, m.id');
    $stmt->execute([$today]);
    return $stmt->fetchAll();
}

/** Identifies one person on one subscription, for tick boxes. */
function person_key(array $row): string
{
    return $row['subscription_id'] . '-' . $row['member_id'];
}

/** The rows whose key was ticked and that have a membership number. */
function ticked_people(array $rows, array $keys): array
{
    $keys = array_flip(array_map('strval', $keys));
    return array_values(array_filter(
        $rows,
        fn($row) => isset($keys[person_key($row)]) && $row['membership_number'] !== null
    ));
}

/** Records $today in one of SENT_DATE_COLUMNS for the given rows. */
function mark_people_sent(array $rows, string $column, string $today): int
{
    if (!in_array($column, SENT_DATE_COLUMNS, true)) {
        throw new InvalidArgumentException("Unknown column {$column}");
    }
    $stmt = db()->prepare("UPDATE subscription_members SET {$column} = ? WHERE subscription_id = ? AND member_id = ?");
    foreach ($rows as $row) {
        $stmt->execute([$today, $row['subscription_id'], $row['member_id']]);
    }
    return count($rows);
}

/** The name the member goes by: preferred name if set, otherwise first name. */
function known_as(array $row): string
{
    return trim((string) $row['preferred_name']) !== '' ? $row['preferred_name'] : $row['first_name'];
}
