<?php $isBand = $item['item_type'] === 'wristband'; ?>
<div class="card narrow">
  <p class="hint"><a href="<?= e($back) ?>">← Back to <?= e($member['first_name'] . ' ' . $member['last_name']) ?></a></p>
  <h1><?= e(ACCESS_ITEM_TYPES[$item['item_type']]) ?></h1>
  <p class="hint">Use this to correct a mistake. To give someone a new key or wristbands for the season, use "Issue" on their page instead.</p>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <form method="post" action="access-item-edit.php?id=<?= (int) $item['id'] ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <?php if ($isBand): ?>
      <label>Colour<input type="text" name="colour" value="<?= e($values['colour']) ?>" required maxlength="30"></label>
      <label>How many<input type="number" name="quantity" value="<?= e($values['quantity']) ?>" required min="1" max="20"></label>
    <?php else: ?>
      <label>Code<input type="text" name="identifier" value="<?= e($values['identifier']) ?>" required maxlength="50"></label>
    <?php endif; ?>
    <label>Issued<input type="date" name="issued_date" value="<?= e($values['issued_date']) ?>" required></label>
    <label>Status
      <select name="status">
        <?php foreach (ACCESS_ITEM_STATUSES as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $values['status'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Status date <span class="hint">(when it was returned, lost or disabled; today if left blank)</span>
      <input type="date" name="status_date" value="<?= e($values['status_date']) ?>">
    </label>
    <?php if ($isBand): ?>
      <label>How many were returned <span class="hint">(ignored while Active)</span>
        <input type="number" name="returned_quantity" value="<?= e($values['returned_quantity']) ?>" min="0" max="20">
      </label>
    <?php endif; ?>
    <label>Notes<input type="text" name="notes" value="<?= e($values['notes']) ?>" maxlength="255"></label>
    <button type="submit" class="primary">Save</button>
  </form>
</div>

<div class="card narrow">
  <h2>Delete this record</h2>
  <p>Only for something recorded by mistake, e.g. issued to the wrong member. To record a key coming back, change its status instead.</p>
  <form method="post" action="access-item-edit.php?id=<?= (int) $item['id'] ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete">
    <label class="check"><input type="checkbox" name="confirm_delete" value="1"> Yes, delete it</label>
    <button type="submit" class="danger">Delete record</button>
  </form>
</div>
