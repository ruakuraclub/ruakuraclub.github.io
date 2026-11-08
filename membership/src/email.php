<?php
declare(strict_types=1);

// Emailing members through Brevo transactional email, using templates made in Brevo.
// Transactional emails reach members even if they've unsubscribed from the newsletter,
// so keep these to membership business (renewals, notices), not promotion.
// Needs subscriptions.php and brevo.php.

const EMAIL_WHO = ['current' => 'Current members', 'lapsed' => 'Lapsed members', 'all' => 'Current and lapsed members'];
const EMAIL_MODES = ['individual' => 'One email each (personalised)', 'bcc' => 'One email, everyone in BCC'];
const EMAIL_BATCH_VERSIONS = 1000;   // Brevo: versions per request
const EMAIL_BCC_PER_EMAIL = 98;      // Brevo: 99 recipients per version, one of them the "to"
const EMAIL_TEMPLATES_URL = 'https://app.brevo.com/templates/listing';

/** Active Brevo templates, as [id => [name, subject, sender_email, sender_name]], sorted by name. */
function brevo_templates(): array
{
    $templates = [];
    $page = brevo_request('GET', '/smtp/templates?templateStatus=true&limit=1000');
    foreach ($page['templates'] ?? [] as $t) {
        $templates[(int) $t['id']] = [
            'name'         => (string) $t['name'],
            'subject'      => (string) ($t['subject'] ?? ''),
            'sender_email' => (string) ($t['sender']['email'] ?? ''),
            'sender_name'  => (string) ($t['sender']['name'] ?? ''),
        ];
    }
    uasort($templates, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
    return $templates;
}

/** One template with its HTML, kept for a minute so flicking through a preview doesn't refetch it each time. */
function brevo_template(int $id): array
{
    $cached = $_SESSION['brevo_templates'][$id] ?? null;
    if ($cached && $cached['fetched'] > time() - 60) {
        return $cached;
    }
    $t = brevo_request('GET', "/smtp/templates/{$id}");
    $template = [
        'name'         => (string) ($t['name'] ?? ''),
        'subject'      => (string) ($t['subject'] ?? ''),
        'html'         => (string) ($t['htmlContent'] ?? ''),
        'sender_email' => (string) ($t['sender']['email'] ?? ''),
        'sender_name'  => (string) ($t['sender']['name'] ?? ''),
        'fetched'      => time(),
    ];
    $_SESSION['brevo_templates'][$id] = $template;
    return $template;
}

/**
 * Fills in {{ params.X }} placeholders (and {{ contact.FIRSTNAME }} / {{ contact.LASTNAME }}) the way
 * Brevo will, including a | default:"…" fallback. Other template tags are left as they are,
 * so this is a close preview rather than an exact one. Values are HTML-escaped when $html.
 */
function email_fill(string $text, array $params, bool $html): string
{
    return preg_replace_callback(
        '/\{\{\s*(params|contact)\.([A-Za-z0-9_]+)\s*((?:\|[^}]*)?)\}\}/',
        function (array $m) use ($params, $html): string {
            $value = (string) ($params[$m[2]] ?? '');
            if ($value === '' && preg_match('/default\s*:\s*(["\'])(.*?)\1/', $m[3], $d)) {
                $value = $d[2];
            }
            return $html ? htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $value;
        },
        $text
    );
}

/**
 * Members who can be emailed: current and/or lapsed (never resigned or deceased),
 * optionally only those whose latest subscription expires on or before $expiresBy.
 */
function email_candidates(string $who, string $expiresBy, string $today): array
{
    $stmt = db()->prepare(
        "SELECT m.id, m.membership_number, m.first_name, m.last_name, m.preferred_name, m.email, m.status,
                (SELECT MAX(s.expiry_date) FROM subscription_members sm JOIN subscriptions s ON s.id = sm.subscription_id
                  WHERE sm.member_id = m.id) AS expiry_date
           FROM members m
          WHERE m.status IN ('active', 'lapsed')
          ORDER BY m.last_name, m.first_name, m.id"
    );
    $stmt->execute();
    $rows = [];
    foreach ($stmt->fetchAll() as $m) {
        $current = $m['status'] === 'active' && $m['expiry_date'] !== null && $m['expiry_date'] >= $today;
        $lapsed = !$current && ($m['status'] === 'lapsed' || $m['expiry_date'] !== null);
        if (!($who === 'current' && $current) && !($who === 'lapsed' && $lapsed) && !($who === 'all' && ($current || $lapsed))) {
            continue;
        }
        if ($expiresBy !== '' && ($m['expiry_date'] === null || $m['expiry_date'] > $expiresBy)) {
            continue;
        }
        $m['email_ok'] = filter_var(trim((string) $m['email']), FILTER_VALIDATE_EMAIL) !== false;
        $rows[] = $m;
    }
    return $rows;
}

/**
 * Combines the chosen members into one recipient per email address.
 * Returns [lower-case email => [email, name, member_ids, params]].
 */
function email_recipients(array $members): array
{
    $byEmail = [];
    foreach ($members as $m) {
        if (!$m['email_ok']) {
            continue;
        }
        $key = mb_strtolower(trim($m['email']));
        $byEmail[$key][] = $m;
    }
    $recipients = [];
    foreach ($byEmail as $email => $group) {
        $first = $group[0];
        $firstNames = implode(' & ', array_unique(array_map('known_as', $group)));
        $numbers = implode(', ', array_filter(array_map(fn($m) => (string) $m['membership_number'], $group)));
        $expiry = max(array_map(fn($m) => (string) $m['expiry_date'], $group));
        $recipients[$email] = [
            'email'      => trim($first['email']),
            'name'       => count($group) === 1 ? trim(known_as($first) . ' ' . $first['last_name']) : $firstNames . ' ' . $first['last_name'],
            'member_ids' => array_map(fn($m) => (int) $m['id'], $group),
            'params'     => [
                'FIRSTNAME'         => $firstNames,
                'LASTNAME'          => $first['last_name'],
                'FULLNAME'          => trim($firstNames . ' ' . $first['last_name']),
                'MEMBERSHIP_NUMBER' => $numbers,
                'EXPIRY'            => $expiry !== '' ? date('j F Y', strtotime($expiry)) : '',
            ],
        ];
    }
    return $recipients;
}

/** How many emails have gone out today (each address counts, BCC included). */
function emails_sent_today(): int
{
    $stmt = db()->prepare('SELECT mode, recipient_count FROM email_sends WHERE sent_at >= ?');
    $stmt->execute([date('Y-m-d 00:00:00')]);
    $total = 0;
    foreach ($stmt->fetchAll() as $s) {
        $count = (int) $s['recipient_count'];
        $total += $s['mode'] === 'bcc' ? $count + (int) ceil($count / EMAIL_BCC_PER_EMAIL) : $count;
    }
    return $total;
}

function email_daily_limit(): int
{
    return (int) (config()['brevo']['daily_limit'] ?? 300);
}

/** Number of Brevo emails a send uses: one per recipient, plus the club's own copy per BCC batch. */
function email_cost(array $recipients, string $mode): int
{
    return $mode === 'bcc' ? count($recipients) + (int) ceil(count($recipients) / EMAIL_BCC_PER_EMAIL) : count($recipients);
}

/**
 * Sends a template to the recipients and logs it. For 'bcc', the email is addressed to the
 * template's sender and everyone is in BCC (in batches). Returns the email_sends id.
 */
function email_send(array $template, int $templateId, array $recipients, string $mode, ?int $userId): int
{
    $pdo = db();
    $pdo->prepare('INSERT INTO email_sends (sent_at, user_id, template_id, template_name, subject, mode, recipient_count) VALUES (?, ?, ?, ?, ?, ?, 0)')
        ->execute([date('Y-m-d H:i:s'), $userId, $templateId, $template['name'], $template['subject'], $mode]);
    $sendId = (int) $pdo->lastInsertId();
    $log = $pdo->prepare('INSERT INTO email_send_recipients (send_id, member_id, email, message_id) VALUES (?, ?, ?, ?)');
    $logged = 0;

    // Each batch is logged once Brevo accepts it, so a failure part-way still records what went.
    $batches = $mode === 'bcc'
        ? array_chunk(array_chunk($recipients, EMAIL_BCC_PER_EMAIL), EMAIL_BATCH_VERSIONS)
        : array_chunk(array_map(fn($r) => [$r], $recipients), EMAIL_BATCH_VERSIONS);
    try {
        foreach ($batches as $versions) {
            $response = brevo_request('POST', '/smtp/email', [
                'templateId'      => $templateId,
                'messageVersions' => array_map(fn($group) => $mode === 'bcc'
                    ? ['to' => [['email' => $template['sender_email'], 'name' => $template['sender_name'] ?: $template['sender_email']]],
                       'bcc' => array_map(fn($r) => ['email' => $r['email'], 'name' => $r['name']], $group)]
                    : ['to' => [['email' => $group[0]['email'], 'name' => $group[0]['name']]], 'params' => $group[0]['params']],
                    $versions),
            ]);
            $messageIds = $response['messageIds'] ?? [];
            foreach ($versions as $v => $group) {
                foreach ($group as $r) {
                    foreach ($r['member_ids'] ?: [null] as $memberId) {
                        $log->execute([$sendId, $memberId, $r['email'], $messageIds[$v] ?? ($response['messageId'] ?? null)]);
                    }
                    $logged++;
                }
            }
        }
    } finally {
        $pdo->prepare('UPDATE email_sends SET recipient_count = ? WHERE id = ?')->execute([$logged, $sendId]);
        if ($logged === 0) {
            $pdo->prepare('DELETE FROM email_sends WHERE id = ?')->execute([$sendId]);
        }
    }
    return $sendId;
}

/** Recent sends, newest first, with who sent them. */
function email_sends(int $limit): array
{
    $stmt = db()->prepare(
        'SELECT s.*, u.display_name FROM email_sends s LEFT JOIN users u ON u.id = s.user_id ORDER BY s.sent_at DESC, s.id DESC LIMIT ' . $limit
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function find_email_send(int $id): ?array
{
    $stmt = db()->prepare('SELECT s.*, u.display_name FROM email_sends s LEFT JOIN users u ON u.id = s.user_id WHERE s.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function email_send_recipients(int $sendId): array
{
    $stmt = db()->prepare(
        'SELECT r.*, m.first_name, m.last_name, m.preferred_name, m.membership_number
           FROM email_send_recipients r LEFT JOIN members m ON m.id = r.member_id
          WHERE r.send_id = ? ORDER BY r.email, m.last_name, m.first_name'
    );
    $stmt->execute([$sendId]);
    return $stmt->fetchAll();
}

/** Emails a member has been sent, newest first. */
function member_emails(int $memberId, int $limit = 20): array
{
    $stmt = db()->prepare(
        'SELECT s.id, s.sent_at, s.template_name, s.subject, s.mode, r.email
           FROM email_send_recipients r JOIN email_sends s ON s.id = r.send_id
          WHERE r.member_id = ? ORDER BY s.sent_at DESC, s.id DESC LIMIT ' . $limit
    );
    $stmt->execute([$memberId]);
    return $stmt->fetchAll();
}
