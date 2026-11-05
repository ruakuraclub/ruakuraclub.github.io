<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

render('coming-soon', [
    'title'       => 'Update Brevo mailing list',
    'user'        => require_login(),
    'description' => 'A CSV file of members who have consented to the newsletter, to import into Brevo as contacts.',
]);
