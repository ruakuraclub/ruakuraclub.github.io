<div class="card wide">
  <h1>MYOB contacts</h1>
  <p>Download <strong>MYOB-Contacts.txt</strong> and import it into MYOB as customer contacts
     (MYOB Business: Settings → Import and export data; AccountRight: File → Import Data → Cards).
     There's one contact per subscription: the main member, or the company for corporate memberships.</p>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="get" action="export-myob.php" class="filters" data-live="#results">
    <label>Show
      <select name="filter">
        <?php foreach (MYOB_FILTERS as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $filter === $k ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">Show</button>
  </form>

  <div id="results">
    <p class="hint"><?= count($contacts) ?> contact<?= count($contacts) === 1 ? '' : 's' ?><?= $filter === 'active' ? '' : ' · ' . e(strtolower(MYOB_FILTERS[$filter])) ?>.</p>

    <?php if ($contacts): ?>
      <form method="post" action="export-myob.php">
        <?= csrf_field() ?>
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Send</th><th>Card ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Expires</th></tr></thead>
            <tbody>
              <?php foreach ($contacts as $c): $name = $c['first_name'] === '' ? $c['last_name'] : $c['last_name'] . ', ' . $c['first_name']; ?>
                <tr>
                  <td><input type="checkbox" name="contacts[]" value="<?= e($c['key']) ?>" aria-label="Include <?= e($name) ?>"
                        <?= ($ticked === null || isset($ticked[$c['key']])) ? ' checked' : '' ?>></td>
                  <td><?= e($c['card_id']) ?></td>
                  <td>
                    <?php if ($c['member_id'] !== null): ?>
                      <a href="member-edit.php?id=<?= $c['member_id'] ?>"><?= e($name) ?></a>
                    <?php else: ?>
                      <?= e($name) ?> <span class="hint">(company, <?= $c['count'] ?> subscription<?= $c['count'] === 1 ? '' : 's' ?>)</span>
                    <?php endif; ?>
                  </td>
                  <td><?= e($c['email']) ?></td>
                  <td><?= e($c['phone']) ?></td>
                  <td><?= e(format_date($c['expiry_date'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="form-actions">
          <button type="submit" class="primary">Download MYOB-Contacts.txt</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
