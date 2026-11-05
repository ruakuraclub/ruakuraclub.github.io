<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/lookup.php';

lookup_page([
    'table'    => 'membership_types',
    'page'     => 'membership-types.php',
    'title'    => 'Membership types',
    'singular' => 'Membership type',
    'intro'    => 'RCC membership types, e.g. Single, Double. Life types get a $0 subscription each season from Management → Life member renewals.',
    'order'    => 'name',
    'fields'   => [
        'name'       => ['label' => 'Name', 'required' => true, 'max' => 50],
        'annual_fee' => ['label' => 'Annual fee', 'type' => 'money'],
        'is_life'    => ['label' => 'Life membership', 'type' => 'bool'],
    ],
]);
