<div class="card wide">
  <p class="hint"><a href="users.php">← Users</a></p>
  <h1>Login activity</h1>
  <p class="hint">Logins, failed attempts, lockouts and changes to user accounts. Kept for 12 months.
     "By" shows who made a change to someone else's account.</p>

  <form method="get" action="login-activity.php" class="filters" data-live="#results">
    <label>User
      <select name="who">
        <option value="">Everyone</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int) $u['id'] ?>"<?= $who === (string) $u['id'] ? ' selected' : '' ?>><?= e($u['display_name']) ?> (<?= e($u['username']) ?>)</option>
        <?php endforeach; ?>
        <option value="unknown"<?= $who === 'unknown' ? ' selected' : '' ?>>Unknown usernames</option>
      </select>
    </label>
    <label>Show
      <select name="group">
        <option value="">Everything</option>
        <?php foreach (AUTH_EVENT_GROUPS as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $group === $k ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">Show</button>
  </form>

  <div id="results">
    <p class="hint"><?= count($entries) ?> event<?= count($entries) === 1 ? '' : 's' ?><?= count($entries) >= $limit ? " (the latest {$limit})" : '' ?></p>
    <?php if ($entries): $showUser = true; require __DIR__ . '/auth-log-table.php'; endif; ?>
  </div>
</div>
