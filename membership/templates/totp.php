<div class="card narrow">
  <h1>Enter your code</h1>
  <p>Open your authenticator app and enter the 6-digit code for <?= e(config()['app_name']) ?>.</p>
  <?php if ($error): ?>
    <p class="error" role="alert"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post" action="totp.php">
    <?= csrf_field() ?>
    <label>Code
      <input type="text" name="code" class="code" required autofocus autocomplete="one-time-code"
             inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7">
    </label>
    <button type="submit" class="primary">Log in</button>
  </form>
  <p class="hint"><a href="login.php">Start again</a></p>
</div>
