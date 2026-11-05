<?php
// One value per column, so each can be filtered on later.
$code        = fn(array $i): string => (string) $i['identifier'];
$colour      = fn(array $i): string => (string) $i['colour'];
$quantity    = fn(array $i): string => (string) (int) $i['quantity'];
$status      = fn(array $i): string => ACCESS_ITEM_STATUSES[$i['status']] ?? $i['status'];
$statusDate  = fn(array $i): string => $i['status'] === 'active' ? '' : format_date($i['status_date']);
$returnedQty = fn(array $i): string => $i['status'] === 'active' ? '' : (string) (int) $i['returned_quantity'];

// One line per item in a cell.
$lines = fn(array $items, callable $fn): string => $items ? implode('<br>', array_map(fn($i) => e($fn($i)), $items)) : '—';

$totalBands = $totalBandsBack = 0;
foreach ($rows as $r) {
    foreach ($r['items'] as $i) {
        if ($i['item_type'] === 'wristband') {
            $totalBands += (int) $i['quantity'];
            $totalBandsBack += (int) $i['returned_quantity'];
        }
    }
}
?>
<div class="card wide">
  <h1 id="report-title"><?= e($title) ?> <?= e(season_label($season)) ?></h1>
  <form method="get" action="<?= e($page) ?>" class="filters" data-live="#report-title, #results">
    <label>Season issued
      <select name="season">
        <?php foreach ($seasons as $s): ?>
          <option value="<?= e($s) ?>"<?= $s === $season ? ' selected' : '' ?>><?= e(season_label($s)) ?> (<?= e(format_date($s)) ?> – <?= e(format_date(expiry_for_term($s, 1))) ?>)</option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">Show</button>
  </form>

  <div id="results">
  <?php $exportQuery = ['season' => $season]; require __DIR__ . '/report-actions.php'; ?>

  <p class="hint">
    <span class="print-only">Printed <?= e(format_date(date('Y-m-d'))) ?> · </span><?= count($rows) ?> member<?= count($rows) === 1 ? '' : 's' ?> issued <?= $withBands ? 'a key or wristbands' : 'a key' ?> this season<?php if ($withBands): ?>
      · <?= $totalBands ?> wristband<?= $totalBands === 1 ? '' : 's' ?> issued, <?= $totalBandsBack ?> returned<?php endif; ?>.
  </p>

  <?php if ($rows): ?>
    <div class="table-wrap">
      <table class="report">
        <thead>
          <tr>
            <th>No.</th><th>Member</th><th>Key</th><th>Key status</th><th>Key status date</th>
            <?php if ($withBands): ?><th>Band colour</th><th>Bands</th><th>Bands status</th><th>Bands returned</th><th>Bands status date</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $memberId => $r):
              $keys = array_values(array_filter($r['items'], fn($i) => $i['item_type'] === $keyType));
              $bands = array_values(array_filter($r['items'], fn($i) => $i['item_type'] === 'wristband')); ?>
            <tr>
              <td class="num"><?= e((string) $r['member']['membership_number']) ?></td>
              <td><a href="member-edit.php?id=<?= (int) $memberId ?>#access"><?= e($r['member']['last_name'] . ', ' . $r['member']['first_name']) ?></a></td>
              <td><?= $lines($keys, $code) ?></td>
              <td><?= $lines($keys, $status) ?></td>
              <td><?= $lines($keys, $statusDate) ?></td>
              <?php if ($withBands): ?>
                <td><?= $lines($bands, $colour) ?></td>
                <td class="num"><?= $lines($bands, $quantity) ?></td>
                <td><?= $lines($bands, $status) ?></td>
                <td class="num"><?= $lines($bands, $returnedQty) ?></td>
                <td><?= $lines($bands, $statusDate) ?></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  </div>
</div>
