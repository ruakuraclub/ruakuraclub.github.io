<div class="card">
  <h1><?= e($def['title']) ?></h1>
  <p class="hint"><?= e($def['intro']) ?></p>
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>

  <?php if (!$rows): ?>
    <p>None yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <?php foreach ($def['fields'] as $f): ?><th><?= e($f['label']) ?></th><?php endforeach; ?>
            <th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr class="<?= $row['is_active'] ? '' : 'inactive' ?>">
              <?php foreach ($def['fields'] as $col => $f): ?>
                <td class="<?= ['text' => 'wrap', 'bool' => ''][$f['type'] ?? 'text'] ?? 'num' ?>"><?= e(lookup_display($f, $row[$col])) ?></td>
              <?php endforeach; ?>
              <td><?= $row['is_active'] ? 'Active' : 'Deactivated' ?></td>
              <td class="actions">
                <a href="<?= e($def['page']) ?>?edit=<?= (int) $row['id'] ?>#form">Edit</a>
                <form method="post" action="<?= e($def['page']) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <?php if ($row['is_active']): ?>
                    <button type="submit" name="action" value="deactivate" class="link">Deactivate</button>
                  <?php else: ?>
                    <button type="submit" name="action" value="activate" class="link">Reactivate</button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="card narrow">
  <h2 id="form"><?= $editId ? 'Edit' : 'Add' ?> <?= e(mb_strtolower($def['singular'])) ?></h2>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <form method="post" action="<?= e($def['page']) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) $editId ?>">
    <?php foreach ($def['fields'] as $col => $f): ?>
      <?php if (($f['type'] ?? 'text') === 'bool'): ?>
        <label class="check">
          <input type="checkbox" name="<?= e($col) ?>" value="1"<?= $values[$col] === '1' ? ' checked' : '' ?>>
          <?= e($f['label']) ?>
        </label>
        <?php continue; ?>
      <?php endif; ?>
      <label><?= e($f['label']) ?>
        <input type="text" name="<?= e($col) ?>" value="<?= e($values[$col]) ?>"
               <?= !empty($f['required']) ? 'required' : '' ?>
               <?= ($f['type'] ?? 'text') === 'text' ? 'maxlength="' . (int) ($f['max'] ?? 255) . '"' : 'inputmode="decimal"' ?>>
      </label>
    <?php endforeach; ?>
    <button type="submit" class="primary"><?= $editId ? 'Save changes' : 'Add' ?></button>
  </form>
  <?php if ($editId): ?>
    <p class="hint"><a href="<?= e($def['page']) ?>">Cancel</a></p>
  <?php endif; ?>
</div>
