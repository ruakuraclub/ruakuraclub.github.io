<div class="card narrow">
  <h1>My profile</h1>
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>
  <dl class="details">
    <dt>Name</dt><dd><?= e($user['display_name']) ?></dd>
    <dt>Username</dt><dd><?= e($user['username']) ?></dd>
  </dl>
</div>

<div class="card narrow">
  <h2 id="password">Change password</h2>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <form method="post" action="profile.php#password">
    <?= csrf_field() ?>
    <input type="text" name="username" value="<?= e($user['username']) ?>" autocomplete="username" hidden>
    <label>Current password
      <input type="password" name="current_password" required autocomplete="current-password">
    </label>
    <label>New password <span class="hint">(at least 12 characters)</span>
      <input type="password" name="new_password" required minlength="12" autocomplete="new-password">
    </label>
    <label>Confirm new password
      <input type="password" name="new_password_confirm" required minlength="12" autocomplete="new-password">
    </label>
    <button type="submit" class="primary">Change password</button>
  </form>
</div>

<div class="card narrow">
  <h2>Authenticator app</h2>
  <p>Got a new phone, or want to move your codes to a different app? Set up a new authenticator. Your current one keeps working until the new one is confirmed.</p>
  <a class="button" href="profile-totp.php">Set up a new authenticator</a>
</div>
