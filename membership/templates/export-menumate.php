<div class="card wide">
  <h1>Update Menumate</h1>
  <p>Download <strong>Members.csv</strong> and import it into Menumate as patrons.
     Once it's imported, come back and mark them as sent so they drop off this list.</p>
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="get" action="export-menumate.php" class="filters" data-live="#results">
    <label>Show
      <select name="filter">
        <?php foreach (MENUMATE_FILTERS as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $filter === $k ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">Show</button>
  </form>

  <div id="results">
    <p class="hint"><?= count($candidates) ?> member<?= count($candidates) === 1 ? '' : 's' ?> with a current subscription<?= $filter === 'unsent' ? ' not yet sent to Menumate' : '' ?>.</p>

    <?php if ($candidates): ?>
      <form method="post" action="export-menumate.php">
        <?= csrf_field() ?>
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Send</th><th>No.</th><th>Name</th><th>Company</th><th>Expires</th><th>Sent</th></tr></thead>
            <tbody>
              <?php foreach ($candidates as $c): $key = person_key($c); $hasNumber = $c['membership_number'] !== null; ?>
                <tr>
                  <td>
                    <?php if ($hasNumber): ?>
                      <input type="checkbox" name="people[]" value="<?= e($key) ?>" aria-label="Send <?= e(known_as($c) . ' ' . $c['last_name']) ?> to Menumate"
                        <?= ($ticked === null ? !$c['menumate_updated_date'] : isset($ticked[$key])) ? ' checked' : '' ?>>
                    <?php endif; ?>
                  </td>
                  <td class="num"><?= $hasNumber ? e((string) $c['membership_number']) : '<span class="expired">None</span>' ?></td>
                  <td><a href="member-edit.php?id=<?= (int) $c['member_id'] ?>"><?= e($c['last_name'] . ', ' . known_as($c)) ?></a></td>
                  <td><?= e((string) $c['company_name']) ?></td>
                  <td><?= e(format_date($c['expiry_date'])) ?></td>
                  <td><?= $c['menumate_updated_date'] ? e(format_date($c['menumate_updated_date'])) : '—' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (array_filter($candidates, fn($c) => $c['membership_number'] === null)): ?>
          <p class="hint">Members without a membership number can't go to Menumate. Add their number on the member page first.</p>
        <?php endif; ?>
        <div class="form-actions">
          <button type="submit" name="action" value="download" class="primary">Download Members.csv</button>
          <button type="submit" name="action" value="mark">Mark ticked members as sent</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
