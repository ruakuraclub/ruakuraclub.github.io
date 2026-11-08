<?php
declare(strict_types=1);

const MEMBER_STATUSES = ['active', 'lapsed', 'resigned', 'deceased'];

// Editable columns of `members`, in form order.
const MEMBER_TEXT_FIELDS = [
    'first_name' => 100, 'last_name' => 100, 'preferred_name' => 100,
    'email' => 255, 'phone' => 30, 'phone_work' => 30,
    'interest_other' => 150,
    'nominator1_name' => 150, 'nominator1_number' => 20,
    'nominator2_name' => 150, 'nominator2_number' => 20,
];
const MEMBER_DATE_FIELDS = [
    'date_of_birth' => 'Date of birth',
    'newsletter_consent_date' => 'Newsletter consent date',
    'application_received_date' => 'Application received',
    'join_date' => 'Joined (application accepted)',
];

/** The status filter from the URL: a member status or 'all'. Defaults to 'active'. */
function requested_member_status(): string
{
    $status = (string) ($_GET['status'] ?? 'active');
    return $status === 'all' || in_array($status, MEMBER_STATUSES, true) ? $status : 'active';
}

/**
 * Members with their latest subscription expiry, sorted by surname.
 * $status is a member status or 'all'; $q searches names, email and membership number.
 */
function member_list(string $status, string $q = ''): array
{
    $sql = 'SELECT m.id, m.membership_number, m.first_name, m.last_name, m.preferred_name, m.email, m.phone, m.status,
                   (SELECT MAX(s.expiry_date)
                      FROM subscription_members sm
                      JOIN subscriptions s ON s.id = sm.subscription_id
                     WHERE sm.member_id = m.id) AS expiry_date
              FROM members m
             WHERE 1 = 1';
    $params = [];
    if ($status !== 'all') {
        $sql .= ' AND m.status = ?';
        $params[] = $status;
    }
    if ($q !== '') {
        $like = '%' . $q . '%';
        $sql .= " AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.preferred_name LIKE ? OR m.email LIKE ?
                       OR CONCAT(m.first_name, ' ', m.last_name) LIKE ?";
        array_push($params, $like, $like, $like, $like, $like);
        if (ctype_digit($q)) {
            $sql .= ' OR m.membership_number = ?';
            $params[] = (int) $q;
        }
        $sql .= ')';
    }
    $stmt = db()->prepare($sql . ' ORDER BY m.last_name, m.first_name');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function find_member(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM members WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function member_interest_ids(int $memberId): array
{
    $stmt = db()->prepare('SELECT interest_id FROM member_interests WHERE member_id = ?');
    $stmt->execute([$memberId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/** Blank values for a new member's form. */
/** The number after the highest one used, suggested for a new member. */
function next_membership_number(): int
{
    return (int) db()->query('SELECT COALESCE(MAX(membership_number), 0) + 1 FROM members')->fetchColumn();
}

/** Active members with a number, for suggesting nominators. Leaves out $exceptId. */
function nominator_choices(int $exceptId): array
{
    $stmt = db()->prepare(
        "SELECT membership_number, first_name, last_name FROM members
          WHERE status = 'active' AND membership_number IS NOT NULL AND id <> ?
          ORDER BY last_name, first_name"
    );
    $stmt->execute([$exceptId]);
    return $stmt->fetchAll();
}

function member_defaults(): array
{
    $values = array_fill_keys([...array_keys(MEMBER_TEXT_FIELDS), ...array_keys(MEMBER_DATE_FIELDS)], '');
    return $values + ['membership_number' => '', 'address' => '', 'notes' => '', 'newsletter_consent' => 0, 'status' => 'active'];
}

/**
 * Checks the posted form. Returns [values to redisplay, data for the database, errors].
 * $id is the member being edited (0 for new), used for the membership-number check.
 */
function member_validate(array $input, int $id): array
{
    $values = member_defaults();
    $data = [];
    $errors = [];

    foreach (MEMBER_TEXT_FIELDS as $col => $max) {
        $values[$col] = trim((string) ($input[$col] ?? ''));
        if (mb_strlen($values[$col]) > $max) {
            $errors[] = ucfirst(str_replace('_', ' ', $col)) . " must be at most {$max} characters.";
        }
        $data[$col] = $values[$col] === '' ? null : $values[$col];
    }
    foreach (['first_name' => 'First name', 'last_name' => 'Surname'] as $col => $label) {
        if ($values[$col] === '') {
            $errors[] = "{$label} is required.";
        }
    }
    if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address doesn\'t look right.';
    }

    foreach (MEMBER_DATE_FIELDS as $col => $label) {
        $values[$col] = trim((string) ($input[$col] ?? ''));
        $date = clean_date($values[$col]);
        if ($date === null) {
            $errors[] = "{$label} isn't a valid date.";
        }
        $data[$col] = $date ?: null;
    }

    foreach (['address', 'notes'] as $col) {
        $values[$col] = trim((string) ($input[$col] ?? ''));
        $data[$col] = $values[$col] === '' ? null : $values[$col];
    }

    $values['membership_number'] = trim((string) ($input['membership_number'] ?? ''));
    $data['membership_number'] = null;
    if ($values['membership_number'] !== '') {
        if (!ctype_digit($values['membership_number']) || strlen($values['membership_number']) > 9) {
            $errors[] = 'Membership number must be a whole number.';
        } else {
            $data['membership_number'] = (int) $values['membership_number'];
            $stmt = db()->prepare('SELECT first_name, last_name FROM members WHERE membership_number = ? AND id <> ?');
            $stmt->execute([$data['membership_number'], $id]);
            if ($other = $stmt->fetch()) {
                $errors[] = "Membership number {$data['membership_number']} already belongs to {$other['first_name']} {$other['last_name']}.";
            }
        }
    }

    $values['status'] = (string) ($input['status'] ?? 'active');
    if (!in_array($values['status'], MEMBER_STATUSES, true)) {
        $errors[] = 'Choose a status.';
    }
    $data['status'] = $values['status'];

    $values['newsletter_consent'] = !empty($input['newsletter_consent']) ? 1 : 0;
    $data['newsletter_consent'] = $values['newsletter_consent'];
    if (!$data['newsletter_consent']) {
        $data['newsletter_consent_date'] = null;
        $values['newsletter_consent_date'] = '';
    } elseif ($data['newsletter_consent_date'] === null) {
        $data['newsletter_consent_date'] = date('Y-m-d');   // consent given now
    }

    return [$values, $data, $errors];
}

/** Inserts or updates a member and their interests. Returns the member id. */
function member_save(int $id, array $data, array $interestIds): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $columns = array_keys($data);
        if ($id) {
            $set = implode(', ', array_map(fn($c) => "{$c} = ?", $columns));
            $pdo->prepare("UPDATE members SET {$set} WHERE id = ?")->execute([...array_values($data), $id]);
        } else {
            $marks = implode(', ', array_fill(0, count($columns), '?'));
            $pdo->prepare('INSERT INTO members (' . implode(', ', $columns) . ") VALUES ({$marks})")
                ->execute(array_values($data));
            $id = (int) $pdo->lastInsertId();
        }
        $pdo->prepare('DELETE FROM member_interests WHERE member_id = ?')->execute([$id]);
        $insert = $pdo->prepare('INSERT INTO member_interests (member_id, interest_id) VALUES (?, ?)');
        foreach ($interestIds as $interestId) {
            $insert->execute([$id, $interestId]);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return $id;
}
