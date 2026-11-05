<?php
/**
 * Table of login/account events. Included by the Login activity page and a user's Manage page.
 * @var array $entries   from auth_log_entries()
 * @var bool  $showUser  whether to include the User column
 */
?>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Date and time</th><th>Event</th><?php if ($showUser): ?><th>User</th><?php endif; ?><th>By</th><th>IP address</th><th>Browser</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($entries as $entry): [$label, $kind] = AUTH_EVENTS[$entry['event']] ?? [$entry['event'], '']; ?>
        <tr>
          <td><?= e(date('j M Y, g:i:sa', strtotime($entry['created_at']))) ?></td>
          <td class="<?= $kind === 'failure' ? 'expired-text' : '' ?>"><?= e($label) ?></td>
          <?php if ($showUser): ?>
            <td><?= $entry['display_name'] !== null ? e($entry['display_name']) : '<span class="hint">tried "' . e($entry['username']) . '"</span>' ?></td>
          <?php endif; ?>
          <td><?= e($entry['actor_name']) ?></td>
          <td><?= e($entry['ip_address']) ?></td>
          <td title="<?= e($entry['user_agent']) ?>"><?= e(browser_summary($entry['user_agent'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
