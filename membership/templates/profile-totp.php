<div class="card narrow">
  <p class="hint"><a href="profile.php">← My profile</a></p>
  <h1>Set up a new authenticator</h1>
  <ol>
    <li>In your authenticator app, add an account and scan this QR code.</li>
    <li>Enter your password and the 6-digit code the <strong>new</strong> entry shows.</li>
  </ol>
  <img class="qr" src="<?= e($qr) ?>" alt="QR code for your authenticator app" width="220" height="220">
  <p class="hint">Can't scan it? Enter this key in the app instead:<br><code class="secret"><?= e($secret) ?></code></p>
  <?php if ($error): ?>
    <p class="error" role="alert"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post" action="profile-totp.php">
    <?= csrf_field() ?>
    <label>Current password
      <input type="password" name="current_password" required autocomplete="current-password">
    </label>
    <label>Code from the new entry
      <input type="text" name="code" class="code" required autocomplete="one-time-code"
             inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7">
    </label>
    <button type="submit" class="primary">Switch to the new authenticator</button>
  </form>
</div>
