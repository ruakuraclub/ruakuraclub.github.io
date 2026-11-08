<?php
declare(strict_types=1);

// MYOB contacts import: one contact per subscription payer. That's the primary member,
// or the company for corporate memberships (one contact per company).
// MYOB Business (formerly Essentials) and AccountRight share this import layout:
// tab-separated text, "{}" in cell A1, headings on the second row.
// Needs subscriptions.php.

const MYOB_FILTERS = ['active' => 'All current subscriptions', 'unpaid' => 'Not paid yet', 'paid' => 'Paid'];

const MYOB_COLUMNS = [
    'Co./Last Name', 'First Name', 'Card ID', 'Card Status',
    'Addr 1 - Line 1', 'Addr 1 - Line 2', 'Addr 1 - Line 3', 'Addr 1 - Line 4',
    'Addr 1 - Phone No. 1', 'Addr 1 - Phone No. 2', 'Addr 1 - Email', 'Addr 1 - Salutation',
];

/**
 * Payers of current subscriptions, filtered by paid state, sorted by name.
 * A company appears once, however many staff subscriptions it pays for.
 */
function myob_contacts(string $filter, string $today): array
{
    $contacts = [];
    foreach (current_subscription_people($today) as $row) {
        if ($row['role'] !== 'primary') {
            continue;
        }
        $paid = $row['paid_date'] !== null;
        if (($filter === 'paid' && !$paid) || ($filter === 'unpaid' && $paid)) {
            continue;
        }
        if ($row['company_id'] !== null) {
            $key = 'c' . $row['company_id'];
            $contacts[$key] ??= [
                'key' => $key, 'last_name' => $row['company_name'], 'first_name' => '',
                'card_id' => 'C' . $row['company_id'], 'address' => '', 'phone' => '', 'phone_work' => '',
                'email' => '', 'salutation' => '', 'member_id' => null, 'count' => 0, 'expiry_date' => $row['expiry_date'],
            ];
            $contacts[$key]['count']++;
            continue;
        }
        $key = 'm' . $row['member_id'];
        $contacts[$key] = [
            'key' => $key, 'last_name' => $row['last_name'], 'first_name' => $row['first_name'],
            'card_id' => $row['membership_number'] === null ? '' : (string) $row['membership_number'],
            'address' => (string) $row['address'], 'phone' => (string) $row['phone'], 'phone_work' => (string) $row['phone_work'],
            'email' => (string) $row['email'], 'salutation' => known_as($row), 'member_id' => (int) $row['member_id'],
            'count' => 1, 'expiry_date' => $row['expiry_date'],
        ];
    }
    uasort($contacts, fn($a, $b) => [mb_strtolower($a['last_name']), mb_strtolower($a['first_name'])]
                                <=> [mb_strtolower($b['last_name']), mb_strtolower($b['first_name'])]);
    return array_values($contacts);
}

/** Tabs and line breaks would break the columns. */
function myob_text(string $value): string
{
    return trim(preg_replace('/\s+/u', ' ', $value));
}

/** The free-text address split into MYOB's four address lines (any extra lines join the fourth). */
function myob_address_lines(string $address): array
{
    $lines = array_values(array_filter(array_map('myob_text', preg_split('/\R|,/', $address)), fn($l) => $l !== ''));
    if (count($lines) > 4) {
        $lines = [...array_slice($lines, 0, 3), implode(', ', array_slice($lines, 3))];
    }
    return array_pad($lines, 4, '');
}

function myob_line(array $contact): string
{
    return implode("\t", [
        myob_text($contact['last_name']), myob_text($contact['first_name']), $contact['card_id'], 'N',
        ...myob_address_lines($contact['address']),
        myob_text($contact['phone']), myob_text($contact['phone_work']), myob_text($contact['email']), myob_text($contact['salutation']),
    ]);
}

function send_myob_contacts(array $contacts): never
{
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="MYOB-Contacts.txt"');
    echo "{}\r\n", implode("\t", MYOB_COLUMNS), "\r\n";
    foreach ($contacts as $contact) {
        echo myob_line($contact), "\r\n";
    }
    exit;
}
