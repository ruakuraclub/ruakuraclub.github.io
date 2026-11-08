<?php
$field = function (string $col, string $label, string $type = 'text', string $attrs = '') use ($values): string {
    return '<label>' . e($label) . '<input type="' . $type . '" name="' . $col . '" value="' . e($values[$col] ?? '') . '" ' . $attrs . '></label>';
};
?>
<div class="card wide">
  <p class="hint"><a href="members.php">← Members</a></p>
  <h1><?= $member ? e($title) : 'Add member' ?></h1>
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>
  <?php if ($errors): ?>
    <ul class="error" role="alert">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if ($member): ?>
    <section id="subscriptions" class="subs-section">
      <div class="title-row">
        <h2>Subscriptions</h2>
        <a class="button" href="subscription-edit.php?member=<?= (int) $member['id'] ?>">Add subscription</a>
      </div>
      <?php if (!$subscriptions): ?>
        <p class="hint">No subscriptions yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table class="subs">
            <thead><tr><th>Period</th><th></th><th>Type</th><th>Role</th><th>With</th><th>Clubs</th><th>Paid</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($subscriptions as $s):
                  $state = $s['expiry_date'] < $today ? 'expired' : ($s['start_date'] > $today ? 'future' : 'current'); ?>
                <tr>
                  <td><?= e(format_date($s['start_date'])) ?> – <?= e(format_date($s['expiry_date'])) ?></td>
                  <td class="status-<?= $state ?>"><?= ucfirst($state) ?></td>
                  <td><?= e($s['type_name']) ?></td>
                  <td><?= e(SUBSCRIPTION_ROLES[$s['role']] ?? $s['role']) ?></td>
                  <td class="wrap"><?= e(implode(', ', array_map(fn($o) => $o['first_name'] . ' ' . $o['last_name'] . ' (' . $o['role'] . ')', $s['others']))) ?: '—' ?></td>
                  <td class="wrap"><?= e(implode(', ', $s['groups'])) ?: '—' ?></td>
                  <td>
                    <?php if ($s['paid_date']): ?>
                      <?= $s['amount_paid'] !== null ? '$' . e(number_format((float) $s['amount_paid'], 2)) . ' on ' : '' ?><?= e(format_date($s['paid_date'])) ?>
                    <?php else: ?>
                      <span class="expired-text">Not paid</span>
                    <?php endif; ?>
                  </td>
                  <td><a href="subscription-edit.php?id=<?= (int) $s['id'] ?>">Edit</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section id="access" class="subs-section">
      <h2>Cards, keys and wristbands</h2>
      <?php if (!$accessItems): ?>
        <p class="hint">Nothing issued yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Item</th><th>Code / colour</th><th>Qty</th><th>Issued</th><th>Status</th><th>Notes</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($accessItems as $item): ?>
                <tr class="<?= $item['status'] === 'active' ? '' : 'inactive' ?>">
                  <td><?= e(ACCESS_ITEM_TYPES[$item['item_type']] ?? $item['item_type']) ?></td>
                  <td><?= e($item['item_type'] === 'wristband' ? $item['colour'] : $item['identifier']) ?></td>
                  <td class="num"><?= (int) $item['quantity'] ?></td>
                  <td><?= e(format_date($item['issued_date'])) ?></td>
                  <td>
                    <?= e(ACCESS_ITEM_STATUSES[$item['status']] ?? $item['status']) ?>
                    <?php if ($item['status'] !== 'active' && $item['status_date']): ?><span class="hint"><?= e(format_date($item['status_date'])) ?></span><?php endif; ?>
                    <?php if ($item['item_type'] === 'wristband' && $item['status'] !== 'active' && (int) $item['quantity'] > 1): ?>
                      <span class="hint">· <?= (int) $item['returned_quantity'] ?> of <?= (int) $item['quantity'] ?> returned</span>
                    <?php endif; ?>
                  </td>
                  <td class="wrap"><?= e($item['notes']) ?></td>
                  <td class="actions">
                    <a href="access-item-edit.php?id=<?= (int) $item['id'] ?>">Edit</a>
                    <?php if ($item['status'] === 'active'): ?>
                      <form method="post" action="member-edit.php?id=<?= (int) $member['id'] ?>" class="inline-actions">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="item_status">
                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                        <button type="submit" name="status" value="returned" class="link">Returned</button>
                        <button type="submit" name="status" value="lost" class="link">Lost</button>
                        <?php if ($item['item_type'] === 'barrier_card'): ?>
                          <button type="submit" name="status" value="disabled" class="link">Disabled</button>
                        <?php endif; ?>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <details class="issue-form"<?= $itemErrors ? ' open' : '' ?>>
        <summary>Issue a card, key or wristbands</summary>
        <?php if ($itemErrors): ?>
          <ul class="error" role="alert">
            <?php foreach ($itemErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <form method="post" action="member-edit.php?id=<?= (int) $member['id'] ?>#access">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="issue_item">
          <div class="grid">
            <label>Item
              <select name="item_type" required>
                <option value="">Choose…</option>
                <?php foreach (ACCESS_ITEM_TYPES as $k => $label): ?>
                  <option value="<?= e($k) ?>"<?= $itemValues['item_type'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Code <span class="hint">(cards and keys, e.g. A1030)</span>
              <input type="text" name="identifier" value="<?= e($itemValues['identifier']) ?>" maxlength="50">
            </label>
            <label>Colour <span class="hint">(wristbands)</span>
              <input type="text" name="colour" value="<?= e($itemValues['colour']) ?>" maxlength="30">
            </label>
            <label>How many <span class="hint">(wristbands)</span>
              <input type="number" name="quantity" value="<?= e($itemValues['quantity']) ?>" min="1" max="20">
            </label>
            <label>Issued
              <input type="date" name="issued_date" value="<?= e($itemValues['issued_date']) ?>" required>
            </label>
            <label>Notes
              <input type="text" name="notes" value="<?= e($itemValues['notes']) ?>" maxlength="255">
            </label>
            <?php if ($keysHeld): ?>
              <label>Old key returned? <span class="hint">(required when issuing a new <?= e(implode(' or ', array_map(fn($t) => mb_strtolower(ACCESS_ITEM_TYPES[$t]), array_keys($keysHeld)))) ?>)</span>
                <select name="old_key_returned">
                  <option value="">—</option>
                  <option value="yes"<?= $itemValues['old_key_returned'] === 'yes' ? ' selected' : '' ?>>Yes, returned</option>
                  <option value="no"<?= $itemValues['old_key_returned'] === 'no' ? ' selected' : '' ?>>No, not returned</option>
                </select>
              </label>
            <?php endif; ?>
            <?php if ($bandsHeld > 0): ?>
              <label>Old wristbands returned <span class="hint">(required for wristbands: they hold <?= (int) $bandsHeld ?>)</span>
                <input type="number" name="returned" value="<?= e($itemValues['returned']) ?>" min="0" max="<?= (int) $bandsHeld ?>">
              </label>
            <?php endif; ?>
          </div>
          <div class="form-actions">
            <button type="submit" class="primary">Issue</button>
          </div>
        </form>
      </details>
    </section>

    <section id="emails" class="subs-section">
      <h2>Emails sent</h2>
      <?php if (!$emails): ?>
        <p class="hint">None sent from Email members yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Sent</th><th>Template</th><th>Subject</th><th>To</th></tr></thead>
            <tbody>
              <?php foreach ($emails as $em): ?>
                <tr>
                  <td><a href="email-log.php?id=<?= (int) $em['id'] ?>"><?= e(date('j M Y, g:ia', strtotime($em['sent_at']))) ?></a></td>
                  <td><?= e($em['template_name']) ?></td>
                  <td><?= e((string) $em['subject']) ?></td>
                  <td><?= e($em['email']) ?><?= $em['mode'] === 'bcc' ? ' (BCC)' : '' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <h2>Details</h2>
  <?php endif; ?>

  <form method="post" action="member-edit.php<?= $member ? '?id=' . (int) $member['id'] : '' ?>">
    <?= csrf_field() ?>

    <fieldset>
      <legend>Membership</legend>
      <div class="grid">
        <?= $field('membership_number', $member ? 'Membership (card) number' : 'Membership (card) number (next free)', 'text', 'inputmode="numeric" pattern="[0-9]*" maxlength="9" data-select-on-focus') ?>
        <label>Status
          <select name="status">
            <?php foreach ($statuses as $s): ?>
              <option value="<?= e($s) ?>"<?= $values['status'] === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <?= $field('application_received_date', 'Application received', 'date') ?>
        <?= $field('join_date', 'Joined (application accepted)', 'date') ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>Personal details</legend>
      <div class="grid">
        <?= $field('first_name', 'First name(s)', 'text', 'required maxlength="100"') ?>
        <?= $field('last_name', 'Surname', 'text', 'required maxlength="100"') ?>
        <?= $field('preferred_name', 'Preferred name', 'text', 'maxlength="100"') ?>
        <?= $field('date_of_birth', 'Date of birth', 'date') ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>Contact</legend>
      <div class="grid">
        <?= $field('email', 'Email', 'email', 'maxlength="255"') ?>
        <?= $field('phone', 'Phone (home / mobile)', 'tel', 'maxlength="30"') ?>
        <?= $field('phone_work', 'Phone (work)', 'tel', 'maxlength="30"') ?>
        <label class="full">Home address
          <textarea name="address" rows="2"><?= e($values['address']) ?></textarea>
        </label>
        <label class="check full">
          <input type="checkbox" name="newsletter_consent" value="1"<?= $values['newsletter_consent'] ? ' checked' : '' ?>>
          Consents to their email being added to the newsletter mailing list
        </label>
        <?= $field('newsletter_consent_date', 'Consent date (today if left blank)', 'date') ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>Interests</legend>
      <div class="checks">
        <?php foreach ($interests as $i): ?>
          <label class="check">
            <input type="checkbox" name="interests[]" value="<?= (int) $i['id'] ?>"<?= in_array((int) $i['id'], $selectedInterests, true) ? ' checked' : '' ?>>
            <?= e($i['name']) ?><?= $i['is_active'] ? '' : ' <span class="hint">(no longer offered)</span>' ?>
          </label>
        <?php endforeach; ?>
      </div>
      <?= $field('interest_other', 'Other', 'text', 'maxlength="150"') ?>
    </fieldset>

    <fieldset>
      <legend>Nominators (new members)</legend>
      <p class="hint">Start typing an active member's name or card number to pick them; the other box fills in.</p>
      <div class="grid">
        <?= $field('nominator1_name', 'Nominator 1 – name', 'text', 'maxlength="150" list="nominator-names" autocomplete="off" data-nominator="1" data-part="name"') ?>
        <?= $field('nominator1_number', 'Nominator 1 – card number', 'text', 'maxlength="20" list="nominator-numbers" autocomplete="off" data-nominator="1" data-part="number"') ?>
        <?= $field('nominator2_name', 'Nominator 2 – name', 'text', 'maxlength="150" list="nominator-names" autocomplete="off" data-nominator="2" data-part="name"') ?>
        <?= $field('nominator2_number', 'Nominator 2 – card number', 'text', 'maxlength="20" list="nominator-numbers" autocomplete="off" data-nominator="2" data-part="number"') ?>
      </div>
      <datalist id="nominator-names">
        <?php foreach ($nominators as $n): ?>
          <option value="<?= e($n['first_name'] . ' ' . $n['last_name']) ?>" label="No. <?= (int) $n['membership_number'] ?>" data-number="<?= (int) $n['membership_number'] ?>"></option>
        <?php endforeach; ?>
      </datalist>
      <datalist id="nominator-numbers">
        <?php foreach ($nominators as $n): ?>
          <option value="<?= (int) $n['membership_number'] ?>" label="<?= e($n['first_name'] . ' ' . $n['last_name']) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </fieldset>

    <fieldset>
      <legend>Notes</legend>
      <textarea name="notes" rows="4" aria-label="Notes"><?= e($values['notes']) ?></textarea>
    </fieldset>

    <div class="form-actions">
      <button type="submit" class="primary"><?= $member ? 'Save changes' : 'Add member' ?></button>
      <a href="members.php">Cancel</a>
    </div>
  </form>
</div>
