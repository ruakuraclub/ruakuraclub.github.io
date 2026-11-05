<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/lookup.php';

lookup_page([
    'table'    => 'club_groups',
    'page'     => 'groups.php',
    'title'    => 'Groups',
    'singular' => 'Group',
    'intro'    => 'Sub-clubs a member can belong to for a subscription, e.g. Squash, Swimming pool. Members must have an RCC subscription to join one.',
    'order'    => 'name',
    'fields'   => [
        'name'        => ['label' => 'Name', 'required' => true, 'max' => 100],
        'description' => ['label' => 'Description', 'max' => 255],
    ],
]);
