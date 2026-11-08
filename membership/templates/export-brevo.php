<?php
$who = function (int $listId) use ($plan, $interestNames): string {
    $roles = [];
    if ($plan['mapping']['main'] === $listId) {
        $roles[] = 'All current members';
    }
    if ($plan['mapping']['lapsed'] === $listId) {
        $roles[] = 'Lapsed members';
    }
    foreach (array_keys($plan['mapping']['interests'], $listId, true) as $interestId) {
        $roles[] = 'Interest: ' . ($interestNames[$interestId] ?? '?');
    }
    return implode(', ', $roles);
};
$names = fn(array $contacts): string => implode(', ', array_map(fn($c) => implode(' / ', $c['names']), $contacts));
?>
<div class="card wide">
  <h1>Update Brevo mailing list</h1>
  <p>Brings the Brevo lists chosen under <a href="brevo-lists.php">Management → Brevo lists</a> up to date with the members here.
     Check the changes below, then send them.</p>
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if (!$configured): ?>
    <p class="notice">Brevo isn't set up yet. The hosting admin needs to add a Brevo API key to <code>config.php</code> (see <code>config.example.php</code>).</p>
  <?php elseif ($plan): ?>
    <?php $adds = array_sum(array_map('count', $plan['add'])); $removes = array_sum(array_map('count', $plan['remove'])); ?>
    <p class="hint">Last sent: <?= $lastSync ? e(date('j M Y, g:ia', strtotime($lastSync))) : 'never' ?>.</p>

    <div class="table-wrap">
      <table>
        <thead><tr><th>Brevo list</th><th>For</th><th class="num">Members who belong</th><th class="num">To add</th><th class="num">To remove</th></tr></thead>
        <tbody>
          <?php foreach ($plan['lists'] as $id => $name): ?>
            <tr>
              <td><?= e($name) ?></td>
              <td><?= e($who((int) $id)) ?></td>
              <td class="num"><?= (int) $plan['on_list'][$id] ?></td>
              <td class="num"><?= count($plan['add'][$id]) ?></td>
              <td class="num"><?= count($plan['remove'][$id]) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php foreach ($plan['lists'] as $id => $name): ?>
      <?php if ($plan['add'][$id] || $plan['remove'][$id]): ?>
        <details>
          <summary><?= e($name) ?>: who changes</summary>
          <?php if ($plan['add'][$id]): ?><p><strong>Add:</strong> <?= e($names($plan['add'][$id])) ?></p><?php endif; ?>
          <?php if ($plan['remove'][$id]): ?><p><strong>Remove:</strong> <?= e($names($plan['remove'][$id])) ?></p><?php endif; ?>
        </details>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($plan['skipped']['no_email'] || $plan['skipped']['bad_email']): ?>
      <p class="hint">Not sent: <?= (int) $plan['skipped']['no_email'] ?> member<?= $plan['skipped']['no_email'] === 1 ? '' : 's' ?> without an email address<?php if ($plan['skipped']['bad_email']): ?>,
        <?= (int) $plan['skipped']['bad_email'] ?> with an email address that doesn't look right<?php endif; ?>.</p>
    <?php endif; ?>

    <form method="post" action="export-brevo.php" class="form-actions">
      <?= csrf_field() ?>
      <button type="submit" class="primary">Send to Brevo</button>
      <span class="hint"><?= $adds || $removes ? "{$adds} addition" . ($adds === 1 ? '' : 's') . ", {$removes} removal" . ($removes === 1 ? '' : 's') . '. Names are refreshed too.' : 'The lists are up to date. Sending again only refreshes names.' ?></span>
    </form>
  <?php endif; ?>
</div>
