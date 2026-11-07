<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/lookup.php';

lookup_page([
    'table'    => 'companies',
    'page'     => 'companies.php',
    'title'    => 'Companies',
    'singular' => 'Company',
    'intro'    => 'Companies that pay for their staff\'s memberships. Choose the company on each staff member\'s subscription.',
    'order'    => 'name',
    'fields'   => [
        'name' => ['label' => 'Name', 'required' => true, 'max' => 100],
    ],
]);
