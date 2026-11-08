<?php
declare(strict_types=1);

// Menumate patron import: Members.csv, laid out like samples/menumate/Members.csv.
// 40 columns, no header row, no quotes, Windows line endings, plain ASCII.
// Most columns are fixed values or deliberately empty. Where each column ends up in
// Menumate (CONTACTS / CONTACTCARDS fields) is noted in IMPROVEMENTS.md.
// Needs subscriptions.php and cards.php (for CLUB_ID).

const MENUMATE_COLUMN_COUNT = 40;

/** Fixed values, by 1-based column number. */
const MENUMATE_FIXED = [1 => '2', 11 => '+', 12 => '0', 14 => '-1', 36 => 'Y', 37 => 'Y', 38 => 'N', 39 => 'N', 40 => 'N'];

/**
 * Makes text safe for the file: macrons and accents become plain letters,
 * and commas, quotes and line breaks (which would break the columns) are removed.
 */
function menumate_text(?string $value): string
{
    $value = strtr((string) $value, [
        'Ā' => 'A', 'ā' => 'a', 'Ē' => 'E', 'ē' => 'e', 'Ī' => 'I', 'ī' => 'i', 'Ō' => 'O', 'ō' => 'o', 'Ū' => 'U', 'ū' => 'u',
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ä' => 'A', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'Ñ' => 'N', 'ñ' => 'n', 'Ç' => 'C', 'ç' => 'c', '’' => "'", '‘' => "'",
    ]);
    $value = preg_replace('/[,"\r\n\t]+/', ' ', $value);
    $value = preg_replace('/[^\x20-\x7E]/', '', $value);   // anything else non-ASCII
    return trim(preg_replace('/ {2,}/', ' ', $value));
}

/** NZ mobile numbers start 02 (or +64 2 / 64 2). */
function menumate_is_mobile(string $phone): bool
{
    $digits = preg_replace('/\D/', '', $phone);
    return str_starts_with($digits, '02') || str_starts_with($digits, '642');
}

/** One line of the file for a person (without the line ending). */
function menumate_line(array $row): string
{
    $number = (string) (int) $row['membership_number'];
    $cols = array_fill(1, MENUMATE_COLUMN_COUNT, '');
    foreach (MENUMATE_FIXED as $i => $value) {
        $cols[$i] = $value;
    }
    $cols[2]  = menumate_text(known_as($row));
    $cols[7]  = menumate_text($row['company_name']);
    $cols[10] = CLUB_ID . $number;
    $cols[13] = $number;
    $cols[15] = menumate_text($row['first_name']);
    $cols[17] = menumate_text($row['last_name']);

    // Columns 3, 4 and 21 were worked out from the order Menumate's importer writes its
    // fields (PHONE, MOBILE, ... DATEOFBIRTH), not seen in a real import yet.
    $phone = menumate_text($row['phone'] ?? '');
    if ($phone !== '') {
        $cols[menumate_is_mobile($phone) ? 4 : 3] = $phone;
    }
    if (!empty($row['date_of_birth'])) {
        $cols[21] = date('d/m/Y', strtotime($row['date_of_birth']));
    }
    return implode(',', $cols);
}

function send_menumate_csv(array $rows): never
{
    header('Content-Type: text/csv; charset=us-ascii');
    header('Content-Disposition: attachment; filename="Members.csv"');
    foreach ($rows as $row) {
        echo menumate_line($row), "\r\n";
    }
    exit;
}
