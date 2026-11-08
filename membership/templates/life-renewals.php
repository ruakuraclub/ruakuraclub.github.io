<div class="card">
  <h1>Life member renewals <?= e($label) ?></h1>
  <p>Creates a $0 subscription from <?= e(format_date($season)) ?> to <?= e(format_date($expiry)) ?> for each active life member who doesn't have one yet.
     It copies the people on their last subscription, but not their sub &amp; affiliate clubs, which are chosen each season.</p>
  <p class="hint">A life member is anyone whose most recent subscription uses a membership type marked "Life membership". Give a new life member their first Life subscription by hand.</p>
  <?php if ($flash): ?>
    <p class="notice" role="status"><?= e($flash) ?></p>
  <?php endif; ?>

  <?php if (!$due): ?>
    <p><strong>All life members are already renewed for <?= e($label) ?>.</strong></p>
  <?php else: ?>
    <h2><?= count($due) ?> to renew</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>No.</th><th>Name</th><th>Type</th><th>Last expiry</th></tr></thead>
        <tbody>
          <?php foreach ($due as $d): ?>
            <tr>
              <td class="num"><?= e((string) $d['membership_number']) ?></td>
              <td><a href="member-edit.php?id=<?= (int) $d['member_id'] ?>#subscriptions"><?= e($d['last_name'] . ', ' . $d['first_name']) ?></a></td>
              <td><?= e($d['type_name']) ?></td>
              <td><?= e(format_date($d['expiry_date'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <form method="post" action="life-renewals.php" class="form-actions">
      <?= csrf_field() ?>
      <button type="submit" class="primary">Renew <?= count($due) ?> life member<?= count($due) === 1 ? '' : 's' ?> for <?= e($label) ?></button>
    </form>
  <?php endif; ?>
</div>
