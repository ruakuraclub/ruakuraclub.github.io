<div class="card">
  <h1>Users</h1>
  <p class="hint">People who can log in to this site. Everyone has the same access. Deactivated users are logged out straight away and can't log in.</p>
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Name</th><th>Username</th><th>Authenticator</th><th>Last login</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr class="<?= $u['is_active'] ? '' : 'inactive' ?>">
            <td><?= e($u['display_name']) ?></td>
            <td><?= e($u['username']) ?></td>
            <td><?= $u['has_totp'] ? 'Set up' : 'Not yet' ?></td>
            <td><?= $u['last_login_at'] ? e(date('j M Y, g:ia', strtotime($u['last_login_at']))) : 'Never' ?></td>
            <td>
              <?= $u['is_active'] ? 'Active' : 'Deactivated' ?>
              <?php if ($u['locked_until'] && strtotime($u['locked_until']) > time()): ?><span class="expired-text">· locked</span><?php endif; ?>
            </td>
            <td class="actions">
              <?php if ((int) $u['id'] === (int) $user['id']): ?>
                <span class="hint">You</span> <a href="profile.php">My profile</a>
              <?php else: ?>
                <a href="user-edit.php?id=<?= (int) $u['id'] ?>">Manage</a>
                <form method="post" action="users.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <?php if ($u['is_active']): ?>
                    <button type="submit" name="action" value="deactivate" class="link">Deactivate</button>
                  <?php else: ?>
                    <button type="submit" name="action" value="activate" class="link">Reactivate</button>
                  <?php endif; ?>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card narrow">
  <h2 id="add">Add a user</h2>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <form method="post" action="users.php#add">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <label>Username
      <input type="text" name="username" value="<?= e($values['username']) ?>" required autocomplete="off">
    </label>
    <label>Name
      <input type="text" name="display_name" value="<?= e($values['display_name']) ?>" required autocomplete="off">
    </label>
    <label>Password <span class="hint">(at least 12 characters)</span>
      <input type="password" name="password" required minlength="12" autocomplete="new-password">
    </label>
    <label>Confirm password
      <input type="password" name="password_confirm" required minlength="12" autocomplete="new-password">
    </label>
    <button type="submit" class="primary">Add user</button>
  </form>
  <p class="hint">They should log in soon after, because the first login is when their authenticator app gets linked.</p>
</div>
