<div class="card wide">
  <h1>Barrier cards</h1>
  <form method="get" action="report-barrier-cards.php" class="filters" data-live="#results">
    <label>Status
      <select name="status">
        <?php foreach (ACCESS_ITEM_STATUSES as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $status === $k ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
        <option value="all"<?= $status === 'all' ? ' selected' : '' ?>>All</option>
      </select>
    </label>
    <button type="submit">Show</button>
  </form>

  <div id="results">
  <?php $page = 'report-barrier-cards.php'; $exportQuery = ['status' => $status]; require __DIR__ . '/report-actions.php'; ?>

  <p class="hint"><?= count($cards) ?> card<?= count($cards) === 1 ? '' : 's' ?><span class="print-only"> · <?= e($status === 'all' ? 'All statuses' : ACCESS_ITEM_STATUSES[$status]) ?> · printed <?= e(format_date(date('Y-m-d'))) ?></span></p>

  <?php if ($cards): ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>No.</th><th>Member</th><th>Card</th><th>Issued</th><th>Status</th><th>Notes</th></tr></thead>
        <tbody>
          <?php foreach ($cards as $c): ?>
            <tr>
              <td class="num"><?= e((string) $c['membership_number']) ?></td>
              <td>
                <a href="member-edit.php?id=<?= (int) $c['member_id'] ?>#access"><?= e($c['last_name'] . ', ' . $c['first_name']) ?></a>
                <?php if ($c['member_status'] !== 'active'): ?><span class="expired-text">(<?= e($c['member_status']) ?>)</span><?php endif; ?>
              </td>
              <td><?= e($c['identifier']) ?></td>
              <td><?= e(format_date($c['issued_date'])) ?></td>
              <td>
                <?= e(ACCESS_ITEM_STATUSES[$c['status']] ?? $c['status']) ?>
                <?php if ($c['status'] !== 'active' && $c['status_date']): ?><span class="hint"><?= e(format_date($c['status_date'])) ?></span><?php endif; ?>
              </td>
              <td class="wrap"><?= e($c['notes']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  </div>
</div>
