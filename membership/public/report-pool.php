<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/subscriptions.php';
require dirname(__DIR__) . '/src/access_items.php';
require dirname(__DIR__) . '/src/reports.php';

season_access_page('Pool access', 'report-pool.php', 'pool_key', true);
