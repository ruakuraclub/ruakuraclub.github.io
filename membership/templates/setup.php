<div class="card narrow">
  <h1>Create the first login</h1>
  <p>This page only works until the first account exists.</p>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <form method="post" action="setup.php">
    <?= csrf_field() ?>
    <label>Username
      <input type="text" name="username" value="<?= e($values['username']) ?>" required autocomplete="username">
    </label>
    <label>Your name
      <input type="text" name="display_name" value="<?= e($values['display_name']) ?>" required autocomplete="name">
    </label>
    <label>Password <span class="hint">(at least 12 characters)</span>
      <input type="password" name="password" required minlength="12" autocomplete="new-password">
    </label>
    <label>Confirm password
      <input type="password" name="password_confirm" required minlength="12" autocomplete="new-password">
    </label>
    <button type="submit" class="primary">Create account</button>
  </form>
</div>
