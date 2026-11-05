<?php
/**
 * Download / print buttons for a report. Included by report templates.
 * @var string $page        the report's page, e.g. 'report-pool.php'
 * @var array  $exportQuery the current filters, e.g. ['season' => '2026-11-01']
 */
$link = fn(string $format) => $page . '?' . http_build_query($exportQuery + ['format' => $format]);
?>
<div class="report-actions">
  <a class="button secondary" href="<?= e($link('xlsx')) ?>">Download Excel</a>
  <a class="button secondary" href="<?= e($link('csv')) ?>">Download CSV</a>
  <button type="button" class="button secondary" data-print>Print / save as PDF</button>
</div>
