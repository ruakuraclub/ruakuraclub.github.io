<div class="card narrow">
  <h1>Log in</h1>
  <?php if ($created): ?>
    <p class="notice">Account created. Log in to set up your authenticator app.</p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="error" role="alert"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post" action="login.php">
    <?= csrf_field() ?>
    <label>Username
      <input type="text" name="username" value="<?= e($username) ?>" required autofocus autocomplete="username">
    </label>
    <label>Password
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button type="submit" class="primary">Continue</button>
  </form>
</div>
