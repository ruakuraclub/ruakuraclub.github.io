<?php
declare(strict_types=1);

// Brevo (mailing lists). The app keeps chosen Brevo lists in step with members:
//  - current members who consented: the main list + the list of each interest they ticked
//  - lapsed members who consented: the lapsed list only
//  - everyone else (no consent, resigned, deceased): taken off all of those lists
// Only lists chosen under Management → Brevo lists are touched, and only for
// email addresses that belong to members. Anyone else on a list is left alone.

const BREVO_REMOVE_CHUNK = 150;

final class BrevoError extends RuntimeException {}

function brevo_configured(): bool
{
    return trim((string) (config()['brevo']['api_key'] ?? '')) !== '';
}

/** Calls the Brevo API and returns the decoded JSON. Throws BrevoError with a readable message. */
function brevo_request(string $method, string $path, ?array $body = null): array
{
    $settings = config()['brevo'] ?? [];
    $url = rtrim((string) ($settings['api_url'] ?? 'https://api.brevo.com/v3'), '/') . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'api-key: ' . trim((string) $settings['api_key']),
            'accept: application/json',
            'content-type: application/json',
        ],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
    }
    $response = curl_exec($ch);
    if ($response === false) {
        throw new BrevoError("Couldn't reach Brevo (" . curl_error($ch) . '). Try again in a minute.');
    }
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $data = $response === '' ? [] : (json_decode($response, true) ?? []);
    if ($status >= 400) {
        throw new BrevoError(match ($status) {
            401     => 'Brevo rejected the API key. Check the key in config.php.',
            429     => 'Brevo says there have been too many requests. Wait a minute and try again.',
            default => "Brevo returned an error ({$status}): " . ($data['message'] ?? 'no details'),
        });
    }
    return $data;
}

/** All Brevo lists, as [id => name], sorted by name. */
function brevo_lists(): array
{
    $lists = [];
    for ($offset = 0; ; $offset += 50) {
        $page = brevo_request('GET', "/contacts/lists?limit=50&offset={$offset}");
        foreach ($page['lists'] ?? [] as $list) {
            $lists[(int) $list['id']] = (string) $list['name'];
        }
        if (count($page['lists'] ?? []) < 50) {
            break;
        }
    }
    natcasesort($lists);
    return $lists;
}

/** Email addresses (lower case) currently on a Brevo list. */
function brevo_list_emails(int $listId): array
{
    $emails = [];
    for ($offset = 0; ; $offset += 500) {
        $page = brevo_request('GET', "/contacts/lists/{$listId}/contacts?limit=500&offset={$offset}");
        foreach ($page['contacts'] ?? [] as $contact) {
            if (!empty($contact['email'])) {
                $emails[mb_strtolower($contact['email'])] = true;
            }
        }
        if (count($page['contacts'] ?? []) < 500) {
            break;
        }
    }
    return $emails;
}

function setting(string $name): ?string
{
    $stmt = db()->prepare('SELECT value FROM settings WHERE name = ?');
    $stmt->execute([$name]);
    $value = $stmt->fetchColumn();
    return $value === false ? null : $value;
}

function set_setting(string $name, ?string $value): void
{
    db()->prepare('DELETE FROM settings WHERE name = ?')->execute([$name]);
    if ($value !== null) {
        db()->prepare('INSERT INTO settings (name, value) VALUES (?, ?)')->execute([$name, $value]);
    }
}

/** The chosen lists: ['main' => ?id, 'lapsed' => ?id, 'interests' => [interest id => list id]] (active interests only). */
function brevo_mapping(): array
{
    $interests = [];
    foreach (db()->query('SELECT id, brevo_list_id FROM interests WHERE is_active = 1 AND brevo_list_id IS NOT NULL') as $row) {
        $interests[(int) $row['id']] = (int) $row['brevo_list_id'];
    }
    $main = setting('brevo_main_list_id');
    $lapsed = setting('brevo_lapsed_list_id');
    return [
        'main'      => $main === null ? null : (int) $main,
        'lapsed'    => $lapsed === null ? null : (int) $lapsed,
        'interests' => $interests,
    ];
}

/** Every list the app manages. */
function brevo_managed_lists(array $mapping): array
{
    return array_values(array_unique(array_filter([$mapping['main'], $mapping['lapsed'], ...array_values($mapping['interests'])])));
}

/**
 * Where each member's email should be. Members sharing an email are combined.
 * Returns ['contacts' => [email => [email, first_name, last_name, lists, names]], 'skipped' => [reason => count]].
 * A contact with no lists is one to take off every managed list.
 */
function brevo_desired(array $mapping, string $today): array
{
    $interestsByMember = [];
    foreach (db()->query('SELECT member_id, interest_id FROM member_interests') as $row) {
        $listId = $mapping['interests'][(int) $row['interest_id']] ?? null;
        if ($listId) {
            $interestsByMember[(int) $row['member_id']][] = $listId;
        }
    }

    $stmt = db()->prepare(
        "SELECT m.id, m.first_name, m.last_name, m.preferred_name, m.email, m.status, m.newsletter_consent,
                EXISTS (SELECT 1 FROM subscription_members sm JOIN subscriptions s ON s.id = sm.subscription_id
                         WHERE sm.member_id = m.id AND s.expiry_date >= ?) AS is_current,
                EXISTS (SELECT 1 FROM subscription_members sm WHERE sm.member_id = m.id) AS has_subscription
           FROM members m
          ORDER BY m.last_name, m.first_name, m.id"
    );
    $stmt->execute([$today]);

    $contacts = [];
    $skipped = ['no_email' => 0, 'bad_email' => 0];
    foreach ($stmt->fetchAll() as $m) {
        $email = mb_strtolower(trim((string) $m['email']));
        if ($email === '') {
            $skipped['no_email']++;
            continue;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $skipped['bad_email']++;
            continue;
        }

        $current = $m['status'] === 'active' && $m['is_current'];
        $lapsed = !$current && ($m['status'] === 'lapsed' || ($m['status'] === 'active' && $m['has_subscription']));
        $lists = [];
        if ($m['newsletter_consent']) {
            if ($current) {
                $lists = array_filter([$mapping['main'], ...($interestsByMember[(int) $m['id']] ?? [])]);
            } elseif ($lapsed) {
                $lists = array_filter([$mapping['lapsed']]);
            }
        }

        $name = trim(known_as($m) . ' ' . $m['last_name']);
        if (!isset($contacts[$email])) {
            $contacts[$email] = ['email' => $email, 'first_name' => known_as($m), 'last_name' => $m['last_name'], 'lists' => [], 'names' => []];
        } elseif ($lists && !$contacts[$email]['lists']) {
            // Shared email: name the contact after a member who is actually on a list.
            $contacts[$email]['first_name'] = known_as($m);
            $contacts[$email]['last_name'] = $m['last_name'];
        }
        $contacts[$email]['lists'] = array_values(array_unique([...$contacts[$email]['lists'], ...$lists]));
        $contacts[$email]['names'][] = $name;
    }
    return ['contacts' => $contacts, 'skipped' => $skipped];
}

/**
 * Compares where members should be with what's in Brevo now.
 * Returns ['lists' => [id => name], 'add' => [list id => [email => contact]], 'remove' => same,
 *          'imports' => [[list ids, contacts]], 'on_list' => [list id => count of members], 'skipped' => ...].
 */
function brevo_plan(string $today): array
{
    $mapping = brevo_mapping();
    $managed = brevo_managed_lists($mapping);
    if (!$managed) {
        throw new BrevoError('No Brevo lists are chosen yet. Choose them under Management → Brevo lists.');
    }
    $lists = brevo_lists();
    foreach ($managed as $listId) {
        if (!isset($lists[$listId])) {
            throw new BrevoError("A list chosen under Management → Brevo lists (#{$listId}) no longer exists in Brevo. Choose another there.");
        }
    }

    $desired = brevo_desired($mapping, $today);
    $add = $remove = $onList = [];
    foreach ($managed as $listId) {
        $inBrevo = brevo_list_emails($listId);
        $add[$listId] = $remove[$listId] = [];
        $onList[$listId] = 0;
        foreach ($desired['contacts'] as $email => $contact) {
            $wanted = in_array($listId, $contact['lists'], true);
            $there = isset($inBrevo[$email]);
            $onList[$listId] += $wanted ? 1 : 0;
            if ($wanted && !$there) {
                $add[$listId][$email] = $contact;
            } elseif (!$wanted && $there) {
                $remove[$listId][$email] = $contact;
            }
        }
    }

    // Everyone who belongs on a list is imported (creates new contacts, refreshes names,
    // adds them to their lists), grouped by their exact set of lists.
    $groups = [];
    foreach ($desired['contacts'] as $contact) {
        if ($contact['lists']) {
            $ids = $contact['lists'];
            sort($ids);
            $groups[implode(',', $ids)][] = $contact;
        }
    }
    $imports = [];
    foreach ($groups as $key => $contacts) {
        $imports[] = [array_map('intval', explode(',', (string) $key)), $contacts];
    }

    return [
        'lists'   => array_intersect_key($lists, array_flip($managed)),
        'mapping' => $mapping,
        'add'     => $add,
        'remove'  => $remove,
        'on_list' => $onList,
        'imports' => $imports,
        'skipped' => $desired['skipped'],
    ];
}

/** Sends the plan to Brevo. Returns [contacts imported, list removals]. */
function brevo_sync(array $plan): array
{
    $imported = 0;
    foreach ($plan['imports'] as [$listIds, $contacts]) {
        brevo_request('POST', '/contacts/import', [
            'listIds'                 => $listIds,
            'updateExistingContacts'  => true,
            'emptyContactsAttributes' => false,
            'disableNotification'     => true,
            'jsonBody'                => array_map(fn($c) => [
                'email'      => $c['email'],
                'attributes' => ['FIRSTNAME' => $c['first_name'], 'LASTNAME' => $c['last_name']],
            ], $contacts),
        ]);
        $imported += count($contacts);
    }

    $removed = 0;
    foreach ($plan['remove'] as $listId => $contacts) {
        foreach (array_chunk(array_keys($contacts), BREVO_REMOVE_CHUNK) as $emails) {
            brevo_request('POST', "/contacts/lists/{$listId}/contacts/remove", ['emails' => $emails]);
            $removed += count($emails);
        }
    }

    set_setting('brevo_last_sync', date('Y-m-d H:i:s'));
    return [$imported, $removed];
}
