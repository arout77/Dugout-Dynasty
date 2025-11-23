<?php

// This bootstrap is necessary to load the .env file
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable( __DIR__ );
$dotenv->load();

return
    [
    'paths'         => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds'      => '%%PHINX_CONFIG_DIR%%/db/seeds',
    ],
    'environments'  => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'development',
        'development'             => [
            'adapter' => 'mysql',
            'host'    => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'name'    => $_ENV['DB_NAME'] ?? 'iwf_prospect_mode',
            'user'    => $_ENV['DB_USER'] ?? 'root',
            'pass'    => $_ENV['DB_PASS'] ?? 'root',
            'port'    => $_ENV['DB_PORT'] ?? '3306',
            'charset' => 'utf8mb4',
        ],
        // You could add a 'production' environment here for your live server
    ],
    'version_order' => 'creation',
];
