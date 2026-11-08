<?php $modeLabel = fn(string $mode): string => ['individual' => 'One each', 'bcc' => 'BCC', 'test' => 'Test'][$mode] ?? $mode; ?>
<div class="card wide">
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>

  <?php if ($send): ?>
    <p class="hint"><a href="email-log.php">← Sent emails</a></p>
    <h1><?= e($send['template_name']) ?></h1>
    <dl class="details">
      <dt>Sent</dt><dd><?= e(date('j M Y, g:ia', strtotime($send['sent_at']))) ?></dd>
      <dt>By</dt><dd><?= e((string) ($send['display_name'] ?? '—')) ?></dd>
      <dt>Subject</dt><dd><?= e((string) $send['subject']) ?></dd>
      <dt>Sent as</dt><dd><?= e($modeLabel($send['mode'])) ?></dd>
      <dt>Addresses</dt><dd><?= (int) $send['recipient_count'] ?></dd>
    </dl>
    <p class="hint">To check whether an email was delivered or opened, look it up by address in Brevo under Transactional → Logs.</p>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Email</th><th>Member</th><th>No.</th></tr></thead>
        <tbody>
          <?php foreach ($recipients as $r): ?>
            <tr>
              <td><?= e($r['email']) ?></td>
              <td><?= $r['member_id'] ? '<a href="member-edit.php?id=' . (int) $r['member_id'] . '">' . e($r['last_name'] . ', ' . known_as($r)) . '</a>' : '—' ?></td>
              <td class="num"><?= e((string) ($r['membership_number'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <h1>Sent emails</h1>
    <p class="hint"><?= count($sends) ?> email<?= count($sends) === 1 ? '' : 's' ?><?= count($sends) >= $limit ? " (the latest {$limit})" : '' ?>.</p>
    <?php if ($sends): ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Sent</th><th>Template</th><th>Subject</th><th>Sent as</th><th class="num">Addresses</th><th>By</th></tr></thead>
          <tbody>
            <?php foreach ($sends as $s): ?>
              <tr>
                <td><a href="email-log.php?id=<?= (int) $s['id'] ?>"><?= e(date('j M Y, g:ia', strtotime($s['sent_at']))) ?></a></td>
                <td><?= e($s['template_name']) ?></td>
                <td><?= e((string) $s['subject']) ?></td>
                <td><?= e($modeLabel($s['mode'])) ?></td>
                <td class="num"><?= (int) $s['recipient_count'] ?></td>
                <td><?= e((string) ($s['display_name'] ?? '—')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
