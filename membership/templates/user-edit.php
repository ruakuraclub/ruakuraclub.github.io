<?php $self = 'user-edit.php?id=' . (int) $target['id']; ?>
<div class="card narrow">
  <p class="hint"><a href="users.php">← Users</a></p>
  <h1><?= e($target['display_name']) ?></h1>
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <dl class="details">
    <dt>Username</dt><dd><?= e($target['username']) ?></dd>
    <dt>Status</dt><dd><?= $target['is_active'] ? 'Active' : 'Deactivated' ?><?= $locked ? ' · locked after too many failed attempts' : '' ?></dd>
    <dt>Authenticator</dt><dd><?= $target['totp_secret'] ? 'Set up' : 'Not set up yet (happens at their next login)' ?></dd>
    <dt>Last login</dt><dd><?= $target['last_login_at'] ? e(date('j M Y, g:ia', strtotime($target['last_login_at']))) : 'Never' ?></dd>
  </dl>
  <?php if ($locked): ?>
    <form method="post" action="<?= e($self) ?>" class="form-actions">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="unlock">
      <button type="submit" class="primary">Unlock now</button>
    </form>
  <?php endif; ?>
</div>

<div class="card narrow">
  <h2>Name</h2>
  <form method="post" action="<?= e($self) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="name">
    <label>Name<input type="text" name="display_name" value="<?= e($displayName) ?>" required maxlength="100"></label>
    <button type="submit" class="primary">Save name</button>
  </form>
</div>

<div class="card narrow">
  <h2>Forgotten password</h2>
  <p>Set a new password and give it to them privately. They can change it themselves under My profile. This also unlocks the account.</p>
  <form method="post" action="<?= e($self) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="password">
    <label>New password <span class="hint">(at least 12 characters)</span>
      <input type="password" name="password" required minlength="12" autocomplete="new-password">
    </label>
    <label>Confirm new password
      <input type="password" name="password_confirm" required minlength="12" autocomplete="new-password">
    </label>
    <button type="submit" class="primary">Set new password</button>
  </form>
</div>

<div class="card narrow">
  <h2>Lost phone / authenticator</h2>
  <p>If they can't get codes any more (lost or replaced phone, deleted the app), reset their authenticator. Their next login will ask them to scan a new QR code, so ask them to log in soon afterwards.</p>
  <form method="post" action="<?= e($self) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="reset_totp">
    <label class="check"><input type="checkbox" name="confirm_reset" value="1"> Yes, reset their authenticator</label>
    <button type="submit" class="danger">Reset authenticator</button>
  </form>
</div>

<div class="card wide">
  <div class="title-row">
    <h2>Recent activity</h2>
    <a href="login-activity.php?who=<?= (int) $target['id'] ?>">All activity</a>
  </div>
  <?php if (!$entries): ?>
    <p class="hint">Nothing recorded yet.</p>
  <?php else: $showUser = false; require __DIR__ . '/auth-log-table.php'; endif; ?>
</div>
