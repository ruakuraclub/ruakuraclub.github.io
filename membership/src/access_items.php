<?php
declare(strict_types=1);

// Cards, keys and wristbands issued to a member. Cards and keys have a code (e.g. A1030);
// wristbands don't, so they're recorded by colour (it changes each season) and quantity.
// Pool/tennis keys and wristbands are replaced each year: issuing new ones closes off the
// member's current ones, recording whether they came back.

const ACCESS_ITEM_TYPES = [
    'barrier_card' => 'Barrier arm access card',
    'pool_key'     => 'Pool key',
    'tennis_key'   => 'Tennis key',
    'wristband'    => 'Pool wristbands',
];
const ACCESS_ITEM_STATUSES = ['active' => 'Active', 'returned' => 'Returned', 'lost' => 'Lost', 'disabled' => 'Disabled'];
const YEARLY_KEY_TYPES = ['pool_key', 'tennis_key'];

function member_access_items(int $memberId): array
{
    $stmt = db()->prepare(
        "SELECT * FROM access_items WHERE member_id = ?
          ORDER BY CASE status WHEN 'active' THEN 0 ELSE 1 END, issued_date DESC, id DESC"
    );
    $stmt->execute([$memberId]);
    return $stmt->fetchAll();
}

/** The colour most recently issued, as the default for the next wristbands. */
function last_wristband_colour(): string
{
    $colour = db()->query("SELECT colour FROM access_items WHERE item_type = 'wristband' ORDER BY id DESC LIMIT 1")->fetchColumn();
    return (string) ($colour ?: '');
}

/** How many of an item type the member currently holds (active), e.g. 2 wristbands or 1 pool key. */
function items_held(int $memberId, string $type): int
{
    $stmt = db()->prepare("SELECT COALESCE(SUM(quantity), 0) FROM access_items WHERE member_id = ? AND item_type = ? AND status = 'active'");
    $stmt->execute([$memberId, $type]);
    return (int) $stmt->fetchColumn();
}

function access_item_defaults(): array
{
    return [
        'item_type' => '', 'identifier' => '', 'colour' => last_wristband_colour(), 'quantity' => '1',
        'issued_date' => date('Y-m-d'), 'notes' => '', 'returned' => '', 'old_key_returned' => '',
    ];
}

/** Returns [values to redisplay, data for the database, errors]. */
function access_item_validate(array $input, int $memberId): array
{
    $values = [];
    foreach (['item_type', 'identifier', 'colour', 'quantity', 'issued_date', 'notes', 'returned', 'old_key_returned'] as $k) {
        $values[$k] = trim((string) ($input[$k] ?? ''));
    }
    $values['identifier'] = mb_strtoupper($values['identifier']);
    $errors = [];
    $type = $values['item_type'];

    if (!isset(ACCESS_ITEM_TYPES[$type])) {
        $errors[] = 'Choose what is being issued.';
    }
    $data = ['item_type' => $type, 'identifier' => null, 'colour' => null, 'quantity' => 1, 'returned' => 0];

    if (in_array($type, YEARLY_KEY_TYPES, true) && items_held($memberId, $type) > 0) {
        if (!in_array($values['old_key_returned'], ['yes', 'no'], true)) {
            $errors[] = 'They already hold a ' . mb_strtolower(ACCESS_ITEM_TYPES[$type]) . '. Say whether the old one was returned.';
        }
        $data['returned'] = $values['old_key_returned'] === 'yes' ? PHP_INT_MAX : 0;
    }

    if ($type === 'wristband') {
        $held = items_held($memberId, 'wristband');
        if ($held > 0) {
            if (!ctype_digit($values['returned']) || (int) $values['returned'] > $held) {
                $errors[] = "Enter how many of their {$held} current wristband" . ($held === 1 ? '' : 's') . " were returned (0–{$held}).";
            }
            $data['returned'] = (int) $values['returned'];
        }
        if ($values['colour'] === '' || mb_strlen($values['colour']) > 30) {
            $errors[] = 'Enter the wristband colour (up to 30 characters).';
        }
        if (!ctype_digit($values['quantity']) || (int) $values['quantity'] < 1 || (int) $values['quantity'] > 20) {
            $errors[] = 'Enter how many wristbands (1–20).';
        }
        $data['colour'] = $values['colour'];
        $data['quantity'] = (int) $values['quantity'];
    } elseif ($type !== '') {
        if ($values['identifier'] === '' || mb_strlen($values['identifier']) > 50) {
            $errors[] = 'Enter the ' . mb_strtolower(ACCESS_ITEM_TYPES[$type] ?? 'item') . ' code.';
        } else {
            $stmt = db()->prepare(
                "SELECT m.first_name, m.last_name FROM access_items a JOIN members m ON m.id = a.member_id
                  WHERE a.item_type = ? AND a.identifier = ? AND a.status = 'active'"
            );
            $stmt->execute([$type, $values['identifier']]);
            if ($holder = $stmt->fetch()) {
                $errors[] = ACCESS_ITEM_TYPES[$type] . " {$values['identifier']} is already issued to {$holder['first_name']} {$holder['last_name']}. Mark it returned or lost on their page first.";
            }
        }
        $data['identifier'] = $values['identifier'];
    }

    $issued = clean_date($values['issued_date']);
    if (!$issued) {
        $errors[] = 'Enter a valid issue date (the reports go by it).';
    }
    $data['issued_date'] = $issued ?: null;

    if (mb_strlen($values['notes']) > 255) {
        $errors[] = 'Notes must be at most 255 characters.';
    }
    $data['notes'] = $values['notes'] === '' ? null : $values['notes'];

    return [$values, $data, $errors];
}

/**
 * Records an issued item. New wristbands or pool/tennis keys replace the member's current ones
 * of that type, so those are closed off: Returned if they came back, Lost if not
 * (for wristbands, with how many came back).
 */
function access_item_issue(int $memberId, array $data): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($data['item_type'] === 'wristband' || in_array($data['item_type'], YEARLY_KEY_TYPES, true)) {
            $stmt = $pdo->prepare("SELECT id, quantity FROM access_items WHERE member_id = ? AND item_type = ? AND status = 'active' ORDER BY id");
            $stmt->execute([$memberId, $data['item_type']]);
            $close = $pdo->prepare('UPDATE access_items SET status = ?, status_date = ?, returned_quantity = ? WHERE id = ?');
            $remaining = $data['returned'];
            foreach ($stmt->fetchAll() as $old) {
                $back = min($remaining, (int) $old['quantity']);
                $remaining -= $back;
                $close->execute([$back === (int) $old['quantity'] ? 'returned' : 'lost', date('Y-m-d'), $back, $old['id']]);
            }
        }
        $pdo->prepare(
            'INSERT INTO access_items (member_id, item_type, identifier, colour, quantity, issued_date, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$memberId, $data['item_type'], $data['identifier'], $data['colour'], $data['quantity'], $data['issued_date'], $data['notes']]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

function find_access_item(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM access_items WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Checks a correction to an existing item (any field except its type).
 * Returns [values to redisplay, data for the database, errors].
 */
function access_item_edit_validate(array $item, array $input): array
{
    $values = [];
    foreach (['identifier', 'colour', 'quantity', 'returned_quantity', 'issued_date', 'status', 'status_date', 'notes'] as $k) {
        $values[$k] = trim((string) ($input[$k] ?? ''));
    }
    $values['identifier'] = mb_strtoupper($values['identifier']);
    $isBand = $item['item_type'] === 'wristband';
    $errors = [];
    $data = [];

    if (!isset(ACCESS_ITEM_STATUSES[$values['status']])) {
        $errors[] = 'Choose a status.';
    }
    $data['status'] = $values['status'];

    if ($isBand) {
        if ($values['colour'] === '' || mb_strlen($values['colour']) > 30) {
            $errors[] = 'Enter the wristband colour (up to 30 characters).';
        }
        $qty = ctype_digit($values['quantity']) ? (int) $values['quantity'] : 0;
        if ($qty < 1 || $qty > 20) {
            $errors[] = 'Enter how many wristbands (1–20).';
        }
        $back = ctype_digit($values['returned_quantity']) ? (int) $values['returned_quantity'] : -1;
        if ($values['status'] === 'active') {
            $back = 0;
        } elseif ($back < 0 || $back > $qty) {
            $errors[] = "Enter how many were returned (0–{$qty}).";
        }
        $data += ['identifier' => null, 'colour' => $values['colour'], 'quantity' => $qty, 'returned_quantity' => max(0, $back)];
    } else {
        if ($values['identifier'] === '' || mb_strlen($values['identifier']) > 50) {
            $errors[] = 'Enter the code.';
        } elseif ($values['status'] === 'active') {
            $stmt = db()->prepare(
                "SELECT m.first_name, m.last_name FROM access_items a JOIN members m ON m.id = a.member_id
                  WHERE a.item_type = ? AND a.identifier = ? AND a.status = 'active' AND a.id <> ?"
            );
            $stmt->execute([$item['item_type'], $values['identifier'], $item['id']]);
            if ($holder = $stmt->fetch()) {
                $errors[] = "{$values['identifier']} is already active for {$holder['first_name']} {$holder['last_name']}.";
            }
        }
        $data += ['identifier' => $values['identifier'], 'colour' => null, 'quantity' => 1,
                  'returned_quantity' => $values['status'] === 'returned' ? 1 : 0];
    }

    foreach (['issued_date' => 'Issue date', 'status_date' => 'Status date'] as $k => $label) {
        $date = clean_date($values[$k]);
        if ($date === null) {
            $errors[] = "{$label} isn't a valid date.";
        }
        $data[$k] = $date ?: null;
    }
    if (!$data['issued_date'] && !in_array("Issue date isn't a valid date.", $errors, true)) {
        $errors[] = 'Enter the issue date (the reports go by it).';
    }
    if ($values['status'] === 'active') {
        $data['status_date'] = null;
    } elseif ($data['status_date'] === null) {
        $data['status_date'] = date('Y-m-d');
    }

    if (mb_strlen($values['notes']) > 255) {
        $errors[] = 'Notes must be at most 255 characters.';
    }
    $data['notes'] = $values['notes'] === '' ? null : $values['notes'];

    return [$values, $data, $errors];
}

function access_item_update(int $id, array $data): void
{
    $set = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($data)));
    db()->prepare("UPDATE access_items SET {$set} WHERE id = ?")->execute([...array_values($data), $id]);
}

function access_item_delete(int $id): void
{
    db()->prepare('DELETE FROM access_items WHERE id = ?')->execute([$id]);
}

/** Marks an active item returned, lost or disabled. Returns false if it isn't this member's active item. */
function access_item_set_status(int $itemId, int $memberId, string $status): bool
{
    if (!in_array($status, ['returned', 'lost', 'disabled'], true)) {
        return false;
    }
    $stmt = db()->prepare(
        "UPDATE access_items SET status = ?, status_date = ?, returned_quantity = CASE WHEN ? = 'returned' THEN quantity ELSE 0 END
          WHERE id = ? AND member_id = ? AND status = 'active'"
    );
    $stmt->execute([$status, date('Y-m-d'), $status, $itemId, $memberId]);
    return $stmt->rowCount() > 0;
}
