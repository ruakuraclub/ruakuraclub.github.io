<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

render('coming-soon', [
    'title'       => 'Print cards',
    'user'        => require_login(),
    'description' => 'Membership cards for members whose card hasn\'t been printed for their current subscription.',
]);
