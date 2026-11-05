<?php
declare(strict_types=1);

// Read-only lists of who holds what, shown on screen or downloaded.
// Needs subscriptions.php (season helpers) and access_items.php.

require_once __DIR__ . '/export.php';

/** Barrier cards, optionally only those with one status. */
function barrier_card_report(string $status): array
{
    $sql = "SELECT a.*, m.first_name, m.last_name, m.membership_number, m.status AS member_status
              FROM access_items a JOIN members m ON m.id = a.member_id
             WHERE a.item_type = 'barrier_card'";
    $params = [];
    if ($status !== 'all') {
        $sql .= ' AND a.status = ?';
        $params[] = $status;
    }
    $stmt = db()->prepare($sql . ' ORDER BY m.last_name, m.first_name, a.issued_date');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Season start dates (newest first) that have items of these types issued, plus the current season. */
function report_seasons(array $types): array
{
    $marks = implode(', ', array_fill(0, count($types), '?'));
    $stmt = db()->prepare("SELECT DISTINCT issued_date FROM access_items WHERE item_type IN ({$marks}) AND issued_date IS NOT NULL");
    $stmt->execute($types);
    $seasons = [season_start() => true];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $date) {
        $seasons[season_start($date)] = true;
    }
    $seasons = array_keys($seasons);
    rsort($seasons);
    return $seasons;
}

/**
 * Items of these types issued during the season, grouped by member:
 * [member_id => ['member' => [...], 'items' => [item, ...]]], in surname order.
 */
function season_access_report(array $types, string $seasonStart): array
{
    $marks = implode(', ', array_fill(0, count($types), '?'));
    $stmt = db()->prepare(
        "SELECT a.*, m.first_name, m.last_name, m.membership_number
           FROM access_items a JOIN members m ON m.id = a.member_id
          WHERE a.item_type IN ({$marks}) AND a.issued_date BETWEEN ? AND ?
          ORDER BY m.last_name, m.first_name, a.issued_date, a.id"
    );
    $stmt->execute([...$types, $seasonStart, expiry_for_term($seasonStart, 1)]);
    $rows = [];
    foreach ($stmt->fetchAll() as $item) {
        $id = (int) $item['member_id'];
        $rows[$id] ??= ['member' => $item, 'items' => []];
        $rows[$id]['items'][] = $item;
    }
    return $rows;
}

/** Barrier cards as a download table: one row per card. */
function barrier_card_export(array $cards): array
{
    $headers = ['Membership no.', 'Surname', 'First name', 'Member status', 'Card', 'Issued', 'Status', 'Status date', 'Notes'];
    $rows = array_map(fn($c) => [
        $c['membership_number'] !== null ? (int) $c['membership_number'] : null,
        $c['last_name'], $c['first_name'], ucfirst($c['member_status']),
        $c['identifier'], $c['issued_date'],
        ACCESS_ITEM_STATUSES[$c['status']] ?? $c['status'],
        $c['status'] === 'active' ? null : $c['status_date'],
        $c['notes'],
    ], $cards);
    return [$headers, $rows];
}

/** Pool/tennis items as a download table: one row per key or set of wristbands. */
function season_access_export(array $reportRows, bool $withBands): array
{
    $headers = ['Membership no.', 'Surname', 'First name', 'Item', 'Key', 'Issued', 'Status', 'Status date'];
    if ($withBands) {
        array_splice($headers, 5, 0, ['Band colour', 'Bands']);
        $headers[] = 'Bands returned';
    }
    $headers[] = 'Notes';

    $rows = [];
    foreach ($reportRows as $r) {
        foreach ($r['items'] as $i) {
            $isBand = $i['item_type'] === 'wristband';
            $row = [
                $r['member']['membership_number'] !== null ? (int) $r['member']['membership_number'] : null,
                $r['member']['last_name'], $r['member']['first_name'],
                ACCESS_ITEM_TYPES[$i['item_type']] ?? $i['item_type'],
                $isBand ? null : $i['identifier'],
            ];
            if ($withBands) {
                array_push($row, $isBand ? $i['colour'] : null, $isBand ? (int) $i['quantity'] : null);
            }
            array_push($row, $i['issued_date'], ACCESS_ITEM_STATUSES[$i['status']] ?? $i['status'],
                $i['status'] === 'active' ? null : $i['status_date']);
            if ($withBands) {
                $row[] = $isBand && $i['status'] !== 'active' ? (int) $i['returned_quantity'] : null;
            }
            $row[] = $i['notes'];
            $rows[] = $row;
        }
    }
    return [$headers, $rows];
}

/** The requested download format ('csv' or 'xlsx'), or null to show the page. */
function requested_export_format(): ?string
{
    $format = (string) ($_GET['format'] ?? '');
    return in_array($format, EXPORT_FORMATS, true) ? $format : null;
}

/** Renders the pool or tennis report page, or sends it as a download. */
function season_access_page(string $title, string $page, string $keyType, bool $withBands): never
{
    $user = require_login();
    $types = $withBands ? [$keyType, 'wristband'] : [$keyType];
    $seasons = report_seasons($types);
    $season = (string) ($_GET['season'] ?? $seasons[0]);
    if (!in_array($season, $seasons, true)) {
        $season = $seasons[0];
    }

    if ($format = requested_export_format()) {
        [$headers, $rows] = season_access_export(season_access_report($types, $season), $withBands);
        $name = strtolower(str_replace(' ', '-', $title)) . '-' . str_replace('/', '-', season_label($season));
        send_table($format, $name, $title . ' ' . season_label($season), $headers, $rows);
    }

    render('report-season', [
        'title'     => $title,
        'user'      => $user,
        'page'      => $page,
        'keyType'   => $keyType,
        'withBands' => $withBands,
        'seasons'   => $seasons,
        'season'    => $season,
        'rows'      => season_access_report($types, $season),
    ]);
}
