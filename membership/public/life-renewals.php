<?php
declare(strict_types=1);

// Gives every life member a $0 subscription for the current season, in one go.

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/subscriptions.php';

$user = require_login();
$season = season_start();

if (is_post()) {
    csrf_check();
    $count = renew_life_members($season);
    flash($count === 1 ? 'Renewed 1 life member.' : "Renewed {$count} life members.");
    redirect('life-renewals.php');
}

render('life-renewals', [
    'title'   => 'Life member renewals',
    'user'    => $user,
    'season'  => $season,
    'label'   => season_label($season),
    'expiry'  => expiry_for_term($season, 1),
    'due'     => life_renewals_due($season),
    'flash'   => take_flash(),
]);
