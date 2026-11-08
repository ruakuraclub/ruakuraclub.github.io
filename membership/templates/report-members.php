<div class="card wide">
  <h1>Members</h1>
  <form method="get" action="report-members.php" class="filters" data-live="#results">
    <label>Status
      <select name="status">
        <option value="all"<?= $status === 'all' ? ' selected' : '' ?>>All</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Company
      <select name="company">
        <option value="">Any (or none)</option>
        <?php foreach ($companies as $id => $name): ?>
          <option value="<?= (int) $id ?>"<?= $companyId === (int) $id ? ' selected' : '' ?>><?= e($name) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">Show</button>
  </form>

  <div id="results">
  <?php $page = 'report-members.php'; $exportQuery = ['status' => $status] + ($companyId ? ['company' => $companyId] : []); require __DIR__ . '/report-actions.php'; ?>

  <p class="hint"><?= count($members) ?> member<?= count($members) === 1 ? '' : 's' ?><?= $companyId ? ' paid for by ' . e($companies[$companyId]) : '' ?><span class="print-only"> · <?= e($status === 'all' ? 'All statuses' : ucfirst($status)) ?> · printed <?= e(format_date($today)) ?></span></p>

  <?php if ($members): ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>No.</th><th>Name</th><th>Expires</th><th>Status</th><th>Company</th></tr></thead>
        <tbody>
          <?php foreach ($members as $m): ?>
            <tr>
              <td class="num"><?= e((string) $m['membership_number']) ?></td>
              <td><a href="member-edit.php?id=<?= (int) $m['id'] ?>"><?= e($m['last_name'] . ', ' . $m['first_name']) ?></a></td>
              <td class="<?= $m['expiry_date'] && $m['expiry_date'] < $today ? 'expired' : '' ?>"><?= $m['expiry_date'] ? e(format_date($m['expiry_date'])) : '—' ?></td>
              <td><?= e(ucfirst($m['status'])) ?></td>
              <td><?= e((string) $m['company_name']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  </div>
</div>
