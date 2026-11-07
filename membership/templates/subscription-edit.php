<?php
$field = function (string $col, string $label, string $type = 'text', string $attrs = '') use ($values): string {
    return '<label>' . e($label) . '<input type="' . $type . '" name="' . $col . '" value="' . e($values[$col] ?? '') . '" ' . $attrs . '></label>';
};
$action = 'subscription-edit.php?' . ($sub ? 'id=' . (int) $sub['id'] : 'member=' . (int) $primaryId);
?>
<div class="card wide">
  <p class="hint"><a href="<?= e($back) ?>">← Back to member</a></p>
  <h1><?= e($title) ?></h1>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">

    <fieldset>
      <legend>Subscription</legend>
      <p class="hint">The expiry date is worked out from the start date and term. You can change it if this subscription needs a different date.</p>
      <div class="grid">
        <label>Membership type
          <select name="membership_type_id" required>
            <option value="">Choose…</option>
            <?php foreach ($types as $t): ?>
              <option value="<?= (int) $t['id'] ?>"<?= (string) $t['id'] === $values['membership_type_id'] ? ' selected' : '' ?>>
                <?= e($t['name']) ?> ($<?= e(number_format((float) $t['annual_fee'], 2)) ?>/yr)
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Company <span class="hint">(if their employer paid)</span>
          <select name="company_id">
            <option value="">—</option>
            <?php foreach ($companies as $c): ?>
              <option value="<?= (int) $c['id'] ?>"<?= (string) $c['id'] === $values['company_id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <?= $field('start_date', 'Start date', 'date', 'required') ?>
        <?= $field('term_years', 'Term (years)', 'number', 'required min="1" max="10"') ?>
        <?= $field('expiry_date', 'Expiry date', 'date') ?>
        <?= $field('student_details', 'Student: Uni/Polytech name and ID no.', 'text', 'maxlength="150"') ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>Payment</legend>
      <div class="grid">
        <?= $field('amount_paid', 'Amount paid ($)', 'text', 'inputmode="decimal"') ?>
        <label>Payment method
          <select name="payment_method">
            <option value="">—</option>
            <?php foreach (PAYMENT_METHODS as $k => $label): ?>
              <option value="<?= e($k) ?>"<?= $values['payment_method'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <?= $field('paid_date', 'Paid date (blank = not paid yet)', 'date') ?>
        <?= $field('payment_reference', 'Payment reference', 'text', 'maxlength="100"') ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>People covered</legend>
      <p class="hint">One primary member, plus a partner for a double membership and any juniors. Groups end when this subscription expires. Use the empty rows to add people.</p>
      <div class="table-wrap">
        <table class="people">
          <thead><tr><th>Member</th><th>Role</th><th>Groups</th><th>Card printed</th></tr></thead>
          <tbody>
            <?php foreach ($values['people'] as $i => $p): ?>
              <tr>
                <td>
                  <select name="people[<?= $i ?>][member_id]" aria-label="Member">
                    <option value="">—</option>
                    <?php foreach ($members as $m): ?>
                      <option value="<?= (int) $m['id'] ?>"<?= (string) $m['id'] === $p['member_id'] ? ' selected' : '' ?>>
                        <?= e($m['last_name'] . ', ' . $m['first_name']) ?><?= $m['membership_number'] ? ' (' . (int) $m['membership_number'] . ')' : '' ?><?= $m['status'] === 'active' ? '' : ' – ' . e($m['status']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td>
                  <select name="people[<?= $i ?>][role]" aria-label="Role">
                    <?php foreach (SUBSCRIPTION_ROLES as $k => $label): ?>
                      <option value="<?= e($k) ?>"<?= $p['role'] === $k || ($p['role'] === '' && $k === ($i === 0 ? 'primary' : 'partner')) ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td>
                  <div class="checks">
                    <?php foreach ($groups as $g): ?>
                      <label class="check">
                        <input type="checkbox" name="people[<?= $i ?>][groups][]" value="<?= (int) $g['id'] ?>"<?= in_array((int) $g['id'], $p['groups'], true) ? ' checked' : '' ?>>
                        <?= e($g['name']) ?>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </td>
                <td>
                  <input type="date" name="people[<?= $i ?>][card_printed_date]" value="<?= e($p['card_printed_date']) ?>" aria-label="Card printed date">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </fieldset>

    <fieldset>
      <legend>Notes</legend>
      <textarea name="notes" rows="3" aria-label="Notes"><?= e($values['notes']) ?></textarea>
    </fieldset>

    <div class="form-actions">
      <button type="submit" class="primary">Save subscription</button>
      <a href="<?= e($back) ?>">Cancel</a>
    </div>
  </form>
</div>

<?php if ($sub): ?>
  <div class="card narrow">
    <h2>Delete this subscription</h2>
    <p>Only for mistakes. It removes the subscription and its group memberships for everyone on it. It can't be undone.</p>
    <form method="post" action="<?= e($action) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <label class="check"><input type="checkbox" name="confirm_delete" value="1"> Yes, delete it</label>
      <button type="submit" class="danger">Delete subscription</button>
    </form>
  </div>
<?php endif; ?>
