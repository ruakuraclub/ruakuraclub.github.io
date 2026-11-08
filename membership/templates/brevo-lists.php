<?php
$select = function (string $name, string $selected, string $label) use ($choices): string {
    $html = '<select name="' . e($name) . '" aria-label="' . e($label) . '"><option value="">— None —</option>';
    foreach ($choices as $id => $listName) {
        $html .= '<option value="' . (int) $id . '"' . ((string) $id === $selected ? ' selected' : '') . '>' . e($listName) . '</option>';
    }
    return $html . '</select>';
};
?>
<div class="card wide">
  <h1>Brevo lists</h1>
  <p>Choose which Brevo lists members are put on when you use <a href="export-brevo.php">Exports → Update Brevo mailing list</a>.
     Only members who have agreed to the newsletter go on any list.</p>
  <ul class="hint">
    <li><strong>Current members</strong> go on the main list, plus the list for each interest they've ticked.</li>
    <li><strong>Lapsed members</strong> are taken off those lists and put on the lapsed list.</li>
    <li>Members without consent, or who have resigned or died, are taken off all of these lists.</li>
    <li>Other people on these lists in Brevo (not members) are left alone. Lists not chosen here aren't touched.</li>
  </ul>
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
  <?php else: ?>
    <form method="post" action="brevo-lists.php">
      <?= csrf_field() ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Who</th><th>Brevo list</th></tr></thead>
          <tbody>
            <tr><td><strong>All current members</strong></td><td><?= $select('main', $values['main'], 'Main list') ?></td></tr>
            <tr><td><strong>Lapsed members</strong></td><td><?= $select('lapsed', $values['lapsed'], 'Lapsed members list') ?></td></tr>
            <?php foreach ($interests as $i): ?>
              <tr>
                <td>Interest: <?= e($i['name']) ?></td>
                <td><?= $select('interests[' . (int) $i['id'] . ']', $values['interests'][$i['id']] ?? '', 'List for ' . $i['name']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="hint">Several interests can share a list. Create new lists in Brevo, then reload this page.</p>
      <div class="form-actions">
        <button type="submit" class="primary"<?= $reachable ? '' : ' disabled' ?>>Save</button>
      </div>
    </form>
  <?php endif; ?>
</div>
