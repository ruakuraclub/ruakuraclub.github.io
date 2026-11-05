<?php
declare(strict_types=1);

// Sends a table (header row + data rows) as a CSV or Excel download.
// Values: strings, ints, or 'YYYY-MM-DD' dates (made real dates in Excel).

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const EXPORT_FORMATS = ['csv', 'xlsx'];

/** Sends the download in the requested format and stops. */
function send_table(string $format, string $filename, string $title, array $headers, array $rows): never
{
    $format === 'xlsx'
        ? send_xlsx($filename, $title, $headers, $rows)
        : send_csv($filename, $headers, $rows);
}

/**
 * Stops a spreadsheet treating text as a formula (e.g. a name starting with "=").
 * Only needed for CSV; the Excel writer stores text as text.
 */
function csv_safe(mixed $value): mixed
{
    return is_string($value) && $value !== '' && strpbrk($value[0], "=+-@\t\r") !== false ? "'" . $value : $value;
}

function send_csv(string $filename, array $headers, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // so Excel reads it as UTF-8 (macrons etc.)
    fputcsv($out, $headers, escape: '');
    foreach ($rows as $row) {
        fputcsv($out, array_map('csv_safe', $row), escape: '');
    }
    fclose($out);
    exit;
}

function send_xlsx(string $filename, string $title, array $headers, array $rows): never
{
    $book = new Spreadsheet();
    $book->getProperties()->setTitle($title)->setCreator(config()['app_name']);
    $sheet = $book->getActiveSheet();
    $sheet->setTitle(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $title), 0, 31));

    $sheet->fromArray($headers, null, 'A1');
    $sheet->getStyle([1, 1, count($headers), 1])->getFont()->setBold(true);

    foreach ($rows as $r => $row) {
        foreach (array_values($row) as $c => $value) {
            $cell = $sheet->getCell([$c + 1, $r + 2]);
            if ($value === null || $value === '') {
                continue;
            }
            if (is_int($value)) {
                $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                $cell->setValueExplicit(ExcelDate::stringToExcel($value), DataType::TYPE_NUMERIC);
                $cell->getStyle()->getNumberFormat()->setFormatCode('d mmm yyyy');
            } else {
                $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            }
        }
    }

    $lastColumn = $sheet->getHighestColumn();
    $sheet->freezePane('A2');
    $sheet->setAutoFilter('A1:' . $lastColumn . max(1, count($rows) + 1));
    foreach (range(1, count($headers)) as $c) {
        $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    (new Xlsx($book))->save('php://output');
    exit;
}
