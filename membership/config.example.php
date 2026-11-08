<?php
// Copy this file to config.php (same folder) and fill in the database details.
// config.php is not committed to git.

return [
    'app_name' => 'Ruakura Club Membership',
    'db' => [
        'dsn'      => 'mysql:host=localhost;dbname=rcc_membership;charset=utf8mb4',
        'user'     => '',
        'password' => '',
    ],
    // Brevo: create a key under Brevo → SMTP & API → API keys. Leave blank to turn the Brevo pages off.
    'brevo' => [
        'api_key' => '',
        'api_url' => 'https://api.brevo.com/v3',
        'daily_limit' => 300,   // emails a day your Brevo plan allows (free plan: 300)
    ],
];
