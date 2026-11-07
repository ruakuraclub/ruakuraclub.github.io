<?php
declare(strict_types=1);

// Membership cards: who needs one, and the spreadsheet the POINTMAN card
// designer (samples/card-printing/RuakuraClub.card) reads. The template reads
// Sheet1 of RuakuraClub.xlsx with the columns in CARD_COLUMNS.

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const CARD_CLUB_ID = 1315;   // goes in front of the membership number in the QR code
const CARD_COLUMNS = ['Number', 'FullName', 'Status', 'Expires', 'QR', 'ClubID'];
const CARD_FILTERS = ['unprinted' => 'Not printed yet', 'all' => 'All current members'];

/**
 * Active members on a subscription that hasn't expired, one row per person
 * (their latest-expiring subscription), sorted by surname.
 */
function card_candidates(string $filter, string $today): array
{
    $sql = "SELECT sm.subscription_id, sm.member_id, sm.role, sm.card_printed_date,
                   s.expiry_date, t.is_life,
                   m.membership_number, m.first_name, m.last_name, m.preferred_name
              FROM subscription_members sm
              JOIN subscriptions s ON s.id = sm.subscription_id
              JOIN membership_types t ON t.id = s.membership_type_id
              JOIN members m ON m.id = sm.member_id
             WHERE m.status = 'active'
               AND s.expiry_date >= ?
               AND s.expiry_date = (SELECT MAX(s2.expiry_date)
                                      FROM subscription_members sm2
                                      JOIN subscriptions s2 ON s2.id = sm2.subscription_id
                                     WHERE sm2.member_id = m.id)";
    if ($filter === 'unprinted') {
        $sql .= ' AND sm.card_printed_date IS NULL';
    }
    $stmt = db()->prepare($sql . ' ORDER BY m.last_name, m.first_name, m.id');
    $stmt->execute([$today]);
    return $stmt->fetchAll();
}

function card_key(array $row): string
{
    return $row['subscription_id'] . '-' . $row['member_id'];
}

function card_full_name(array $row): string
{
    $first = trim((string) $row['preferred_name']) !== '' ? $row['preferred_name'] : $row['first_name'];
    return trim($first . ' ' . $row['last_name']);
}

function card_status(array $row): string
{
    return match (true) {
        (bool) $row['is_life']      => 'Life Member',
        $row['role'] === 'junior'   => 'Junior Member',
        default                     => 'Full Member',
    };
}

/** The rows from $candidates whose key was ticked and that have a membership number. */
function selected_cards(array $candidates, array $keys): array
{
    $keys = array_flip(array_map('strval', $keys));
    return array_values(array_filter(
        $candidates,
        fn($row) => isset($keys[card_key($row)]) && $row['membership_number'] !== null
    ));
}

/** Sends RuakuraClub.xlsx laid out like the sample the card template was built from. */
function send_card_xlsx(array $rows): never
{
    $book = new Spreadsheet();
    $book->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Sheet1');   // the template reads Sheet1$

    $sheet->fromArray(CARD_COLUMNS, null, 'A1');
    foreach ($rows as $i => $row) {
        $r = $i + 2;
        $sheet->getCell("A{$r}")->setValueExplicit((int) $row['membership_number'], DataType::TYPE_NUMERIC);
        $sheet->getCell("B{$r}")->setValueExplicit(card_full_name($row), DataType::TYPE_STRING);
        $sheet->getCell("C{$r}")->setValueExplicit(card_status($row), DataType::TYPE_STRING);
        $sheet->getCell("D{$r}")->setValueExplicit(date('d/m/Y', strtotime($row['expiry_date'])), DataType::TYPE_STRING);
        $sheet->getCell("E{$r}")->setValue("=CONCATENATE(F{$r},A{$r})");
        $sheet->getCell("F{$r}")->setValueExplicit(CARD_CLUB_ID, DataType::TYPE_NUMERIC);
    }

    $sheet->getColumnDimension('C')->setWidth(24.75);
    $sheet->getStyle('D1:D' . (count($rows) + 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
    $sheet->setSelectedCell('A1');

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="RuakuraClub.xlsx"');
    (new Xlsx($book))->save('php://output');
    exit;
}

/** Records today as the card-printed date for the given rows. */
function mark_cards_printed(array $rows, string $today): int
{
    $stmt = db()->prepare('UPDATE subscription_members SET card_printed_date = ? WHERE subscription_id = ? AND member_id = ?');
    foreach ($rows as $row) {
        $stmt->execute([$today, $row['subscription_id'], $row['member_id']]);
    }
    return count($rows);
}
