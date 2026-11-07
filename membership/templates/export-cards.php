<div class="card wide">
  <h1>Print cards</h1>
  <p>Download <strong>RuakuraClub.xlsx</strong>, save it over the one the card designer uses, and print.
     When the cards are done, come back and mark them as printed so they drop off this list.</p>
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="get" action="export-cards.php" class="filters" data-live="#results">
    <label>Show
      <select name="filter">
        <?php foreach (CARD_FILTERS as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $filter === $k ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">Show</button>
  </form>

  <div id="results">
    <p class="hint"><?= count($candidates) ?> member<?= count($candidates) === 1 ? '' : 's' ?> with a current subscription<?= $filter === 'unprinted' ? ' and no card printed yet' : '' ?>.</p>

    <?php if ($candidates): ?>
      <form method="post" action="export-cards.php">
        <?= csrf_field() ?>
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Print</th><th>No.</th><th>Name on card</th><th>Status</th><th>Expires</th><th>Printed</th></tr></thead>
            <tbody>
              <?php foreach ($candidates as $c): $key = person_key($c); $hasNumber = $c['membership_number'] !== null; ?>
                <tr>
                  <td>
                    <?php if ($hasNumber): ?>
                      <input type="checkbox" name="cards[]" value="<?= e($key) ?>" aria-label="Print card for <?= e(card_full_name($c)) ?>"
                        <?= ($ticked === null ? !$c['card_printed_date'] : isset($ticked[$key])) ? ' checked' : '' ?>>
                    <?php endif; ?>
                  </td>
                  <td class="num"><?= $hasNumber ? e((string) $c['membership_number']) : '<span class="expired">None</span>' ?></td>
                  <td><a href="member-edit.php?id=<?= (int) $c['member_id'] ?>"><?= e(card_full_name($c)) ?></a></td>
                  <td><?= e(card_status($c)) ?></td>
                  <td><?= e(format_date($c['expiry_date'])) ?></td>
                  <td><?= $c['card_printed_date'] ? e(format_date($c['card_printed_date'])) : '—' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (array_filter($candidates, fn($c) => $c['membership_number'] === null)): ?>
          <p class="hint">Members without a membership number can't have a card. Add their number on the member page first.</p>
        <?php endif; ?>
        <div class="form-actions">
          <button type="submit" name="action" value="download" class="primary">Download RuakuraClub.xlsx</button>
          <button type="submit" name="action" value="mark">Mark ticked cards as printed</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
