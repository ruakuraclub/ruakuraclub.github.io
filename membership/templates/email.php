<div class="card wide">
  <h1>Email members</h1>
  <p>Send one of your Brevo templates to members, for membership business such as renewal reminders.
     These reach members even if they've unsubscribed from the newsletter, so don't use them for promotion.</p>
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if (!$configured): ?>
    <p class="notice">Brevo isn't set up yet. The hosting admin needs to add a Brevo API key to <code>config.php</code> (see <code>config.example.php</code>).</p>
  <?php else: ?>
    <form method="get" action="email.php" class="filters" data-live="#results">
      <label>Who
        <select name="who">
          <?php foreach (EMAIL_WHO as $k => $label): ?>
            <option value="<?= e($k) ?>"<?= $who === $k ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Expires on or before <span class="hint">(optional)</span>
        <input type="date" name="expires_by" value="<?= e($expiresBy) ?>">
      </label>
      <button type="submit">Show</button>
    </form>

    <form method="post" action="email.php">
      <?= csrf_field() ?>
      <fieldset>
        <legend>Email</legend>
        <div class="grid">
          <label>Template
            <select name="template_id" required>
              <option value="">Choose…</option>
              <?php foreach ($templates as $id => $t): ?>
                <option value="<?= (int) $id ?>"<?= (string) $id === $values['template_id'] ? ' selected' : '' ?>><?= e($t['name']) ?><?= $t['subject'] !== '' ? ' – ' . e($t['subject']) : '' ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Send as
            <select name="mode">
              <?php foreach (EMAIL_MODES as $k => $label): ?>
                <option value="<?= e($k) ?>"<?= $values['mode'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
        <p class="hint"><a href="<?= e(EMAIL_TEMPLATES_URL) ?>" target="_blank" rel="noopener">Edit templates in Brevo ↗</a> (reload this page afterwards).
           In a template, these fill in each member's details:
           <code>{{ params.FIRSTNAME }}</code> <code>{{ params.LASTNAME }}</code> <code>{{ params.FULLNAME }}</code>
           <code>{{ params.MEMBERSHIP_NUMBER }}</code> <code>{{ params.EXPIRY }}</code>.
           They're left blank when sending as BCC, and members sharing an email get one email ("Andrew &amp; Aroha").</p>
        <?php if (!$templates): ?>
          <p class="notice">No active templates found in Brevo. Create one there, then reload this page.</p>
        <?php endif; ?>
      </fieldset>

      <div id="results">
        <input type="hidden" name="who" value="<?= e($who) ?>">
        <input type="hidden" name="expires_by" value="<?= e($expiresBy) ?>">
        <?php
          $sendable = array_filter($candidates, fn($c) => $c['email_ok']);
          $addresses = count(array_unique(array_map(fn($c) => mb_strtolower(trim($c['email'])), $sendable)));
        ?>
        <p class="hint"><?= count($candidates) ?> member<?= count($candidates) === 1 ? '' : 's' ?>,
           <?= $addresses ?> email address<?= $addresses === 1 ? '' : 'es' ?>.
           Today: <?= (int) $sentToday ?> of <?= (int) $limit ?> emails used.</p>
        <?php if ($candidates): ?>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Send</th><th>No.</th><th>Name</th><th>Email</th><th>Expires</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($candidates as $c): ?>
                  <tr>
                    <td>
                      <?php if ($c['email_ok']): ?>
                        <input type="checkbox" name="members[]" value="<?= (int) $c['id'] ?>" aria-label="Email <?= e(known_as($c) . ' ' . $c['last_name']) ?>"
                          <?= ($ticked === null || isset($ticked[(string) $c['id']])) ? ' checked' : '' ?>>
                      <?php endif; ?>
                    </td>
                    <td class="num"><?= e((string) $c['membership_number']) ?></td>
                    <td><a href="member-edit.php?id=<?= (int) $c['id'] ?>"><?= e($c['last_name'] . ', ' . known_as($c)) ?></a></td>
                    <td><?= $c['email_ok'] ? e($c['email']) : '<span class="expired">' . (trim((string) $c['email']) === '' ? 'No email' : e($c['email']) . ' (not valid)') . '</span>' ?></td>
                    <td class="<?= $c['expiry_date'] && $c['expiry_date'] < date('Y-m-d') ? 'expired' : '' ?>"><?= $c['expiry_date'] ? e(format_date($c['expiry_date'])) : '—' ?></td>
                    <td><?= e(ucfirst($c['status'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <fieldset>
        <legend>Send a test first</legend>
        <div class="grid">
          <label>Send a test to
            <input type="email" name="test_email" value="<?= e($values['test_email']) ?>" autocomplete="email">
          </label>
        </div>
        <p class="hint">Filled in with the first ticked member's details.</p>
        <button type="submit" name="action" value="test">Send test</button>
      </fieldset>

      <fieldset>
        <legend>Send</legend>
        <label class="check"><input type="checkbox" name="confirm_send" value="1"> Yes, send this to the ticked members</label>
        <button type="submit" name="action" value="send" class="primary">Send email</button>
      </fieldset>
    </form>
  <?php endif; ?>
</div>
