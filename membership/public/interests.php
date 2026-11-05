<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/lookup.php';

lookup_page([
    'table'    => 'interests',
    'page'     => 'interests.php',
    'title'    => 'Interests',
    'singular' => 'Interest',
    'intro'    => 'The "My interests are" / "Reason for joining" tick boxes. Lower order numbers are listed first.',
    'order'    => 'sort_order, name',
    'fields'   => [
        'name'       => ['label' => 'Name', 'required' => true, 'max' => 100],
        'sort_order' => ['label' => 'Order', 'type' => 'int'],
    ],
]);
