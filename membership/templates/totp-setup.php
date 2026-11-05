<div class="card narrow">
  <h1>Set up your authenticator app</h1>
  <p>Every login needs a code from an authenticator app on your phone, such as Google Authenticator, Microsoft Authenticator or 1Password.</p>
  <ol>
    <li>In the app, add an account and scan this QR code.</li>
    <li>Enter the 6-digit code the app shows.</li>
  </ol>
  <img class="qr" src="<?= e($qr) ?>" alt="QR code for your authenticator app" width="220" height="220">
  <p class="hint">Can't scan it? Enter this key in the app instead:<br><code class="secret"><?= e($secret) ?></code></p>
  <?php if ($error): ?>
    <p class="error" role="alert"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post" action="totp-setup.php">
    <?= csrf_field() ?>
    <label>Code
      <input type="text" name="code" class="code" required autocomplete="one-time-code"
             inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7">
    </label>
    <button type="submit" class="primary">Finish setup</button>
  </form>
</div>
