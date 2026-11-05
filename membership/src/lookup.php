<?php
declare(strict_types=1);

// One screen for the simple lookup tables (groups, interests, membership types):
// list, add, edit, deactivate/reactivate. Rows aren't deleted because members refer to them.
//
// $def keys:
//   table, page (e.g. 'groups.php'), title, singular, intro, order (SQL ORDER BY, after is_active)
//   fields: column => ['label', 'type' => 'text'|'money'|'int'|'bool', 'required' => bool, 'max' => int]
// Table and column names come from code only, never from user input.

function lookup_page(array $def): never
{
    $user = require_login();
    $table = $def['table'];
    $fields = $def['fields'];
    $columns = array_keys($fields);

    $editId = (int) ($_GET['edit'] ?? 0);
    $values = array_fill_keys($columns, '');
    $errors = [];

    if (is_post()) {
        csrf_check();
        $action = (string) ($_POST['action'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);

        if ($action === 'save') {
            $editId = $id;
            [$values, $data, $errors] = lookup_validate($fields, $_POST);

            $stmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE name = ? AND id <> ?");
            $stmt->execute([$data['name'] ?? '', $id]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'There is already one called "' . $data['name'] . '".';
            }

            if (!$errors) {
                if ($id) {
                    $set = implode(', ', array_map(fn($c) => "{$c} = ?", $columns));
                    db()->prepare("UPDATE {$table} SET {$set} WHERE id = ?")->execute([...array_values($data), $id]);
                    flash("Saved {$data['name']}.");
                } else {
                    $marks = implode(', ', array_fill(0, count($columns), '?'));
                    db()->prepare('INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ") VALUES ({$marks})")
                        ->execute(array_values($data));
                    flash("Added {$data['name']}.");
                }
                redirect($def['page']);
            }
        } elseif ($action === 'activate' || $action === 'deactivate') {
            db()->prepare("UPDATE {$table} SET is_active = ? WHERE id = ?")->execute([$action === 'activate' ? 1 : 0, $id]);
            flash($action === 'activate' ? 'Reactivated.' : 'Deactivated. It stays on existing records but is hidden from new ones.');
            redirect($def['page']);
        }
    } elseif ($editId) {
        $stmt = db()->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$editId]);
        $row = $stmt->fetch();
        if (!$row) {
            redirect($def['page']);
        }
        foreach ($columns as $c) {
            $values[$c] = (string) ($row[$c] ?? '');
        }
    }

    $rows = db()->query("SELECT * FROM {$table} ORDER BY is_active DESC, {$def['order']}")->fetchAll();

    render('lookup', [
        'title'  => $def['title'],
        'user'   => $user,
        'def'    => $def,
        'rows'   => $rows,
        'values' => $values,
        'editId' => $editId,
        'errors' => $errors,
        'flash'  => take_flash(),
    ]);
}

/** Returns [form values to redisplay, cleaned data for the database, errors]. */
function lookup_validate(array $fields, array $input): array
{
    $values = $data = $errors = [];
    foreach ($fields as $col => $f) {
        $raw = trim((string) ($input[$col] ?? ''));
        $values[$col] = $raw;
        $type = $f['type'] ?? 'text';

        if ($type === 'bool') {
            $values[$col] = $raw !== '' ? '1' : '0';
            $data[$col] = $raw !== '' ? 1 : 0;
            continue;
        }
        if ($raw === '') {
            if (!empty($f['required'])) {
                $errors[] = "{$f['label']} is required.";
            }
            $data[$col] = $type === 'text' ? null : 0;
            continue;
        }
        if ($type === 'money') {
            if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $raw)) {
                $errors[] = "{$f['label']} must be an amount like 60 or 60.00.";
            }
            $data[$col] = $raw;
        } elseif ($type === 'int') {
            if (!preg_match('/^-?\d{1,5}$/', $raw)) {
                $errors[] = "{$f['label']} must be a whole number.";
            }
            $data[$col] = (int) $raw;
        } else {
            if (mb_strlen($raw) > ($f['max'] ?? 255)) {
                $errors[] = "{$f['label']} must be at most {$f['max']} characters.";
            }
            $data[$col] = $raw;
        }
    }
    return [$values, $data, $errors];
}

function lookup_display(array $f, mixed $value): string
{
    return match ($f['type'] ?? 'text') {
        'money' => '$' . number_format((float) $value, 2),
        'bool'  => $value ? 'Yes' : '',
        default => (string) $value,
    };
}
