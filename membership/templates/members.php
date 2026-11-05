<div class="card wide">
  <div class="title-row">
    <h1>Members</h1>
    <a class="button" href="member-edit.php">Add member</a>
  </div>
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>

  <form method="get" action="members.php" class="filters" data-live="#results">
    <label>Search
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or membership number">
    </label>
    <label>Status
      <select name="status">
        <option value="all"<?= $status === 'all' ? ' selected' : '' ?>>All</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">Search</button>
  </form>

  <div id="results">
  <p class="hint"><?= count($members) ?> member<?= count($members) === 1 ? '' : 's' ?></p>

  <?php if ($members): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>No.</th><th>Name</th><th>Email</th><th>Phone</th><th>Expires</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($members as $m): ?>
            <tr class="<?= $m['status'] === 'active' ? '' : 'inactive' ?>">
              <td class="num"><?= e((string) $m['membership_number']) ?></td>
              <td>
                <a href="member-edit.php?id=<?= (int) $m['id'] ?>"><?= e($m['last_name'] . ', ' . $m['first_name']) ?></a>
                <?php if ($m['preferred_name']): ?><span class="hint">(<?= e($m['preferred_name']) ?>)</span><?php endif; ?>
              </td>
              <td><?= e($m['email']) ?></td>
              <td><?= e($m['phone']) ?></td>
              <td class="<?= $m['expiry_date'] && $m['expiry_date'] < $today ? 'expired' : '' ?>">
                <?= $m['expiry_date'] ? e(date('j M Y', strtotime($m['expiry_date']))) : '—' ?>
              </td>
              <td><?= e(ucfirst($m['status'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  </div>
</div>
