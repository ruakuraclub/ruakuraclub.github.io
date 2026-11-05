<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

render('coming-soon', [
    'title'       => 'Update Menumate',
    'user'        => require_login(),
    'description' => 'A CSV file of members to import into Menumate as patrons.',
]);
