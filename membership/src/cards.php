<?php
declare(strict_types=1);

// Membership cards: the spreadsheet the POINTMAN card designer
// (samples/card-printing/RuakuraClub.card) reads. The template reads
// Sheet1 of RuakuraClub.xlsx with the columns in CARD_COLUMNS.
// Needs subscriptions.php.

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const CLUB_ID = 1315;   // goes in front of the membership number in the card QR code and Menumate
const CARD_COLUMNS = ['Number', 'FullName', 'Status', 'Expires', 'QR', 'ClubID'];

function card_full_name(array $row): string
{
    return trim(known_as($row) . ' ' . $row['last_name']);
}

function card_status(array $row): string
{
    return match (true) {
        (bool) $row['is_life']      => 'Life Member',
        $row['role'] === 'junior'   => 'Junior Member',
        default                     => 'Full Member',
    };
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
        $sheet->getCell("F{$r}")->setValueExplicit(CLUB_ID, DataType::TYPE_NUMERIC);
    }

    $sheet->getColumnDimension('C')->setWidth(24.75);
    $sheet->getStyle('D1:D' . (count($rows) + 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
    $sheet->setSelectedCell('A1');

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="RuakuraClub.xlsx"');
    (new Xlsx($book))->save('php://output');
    exit;
}
