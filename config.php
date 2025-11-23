<?php

/**
 * Rhapsody Framework Configuration
 *
 * This file reads settings directly from the $_ENV superglobal,
 * which is reliably populated by the Dotenv library.
 */

return [
    'logging' => [
        // Leave path empty to disable.
        // Example for MAMP on Windows: 'C:/MAMP/logs/php_error.log'
        // Example for MAMP on macOS: '/Applications/MAMP/logs/php_error.log'
        'php_error_log_path' => 'C:\MAMP\logs\php_error.log',

        // Example for MAMP on Windows: 'C:/MAMP/logs/apache_error.log'
        // Example for MAMP on macOS: '/Applications/MAMP/logs/apache_error.log'
        'apache_error_log_path' => 'C:\MAMP\logs\apache_error.log',
    ],
    /**
     * The base URL of your application.
     */
    'base_url'           => $_ENV['APP_BASE_URL'] ?? '/',
    'app_env'            => $_ENV['APP_ENV'] ?? 'production',
    'app_version'        => 'v1.3.1',
    'theme'              => $_ENV['APP_THEME'] ?? 'default',
    'cache'              => [
        'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
    ],
    'redis'              => [
        'host'     => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port'     => $_ENV['REDIS_PORT'] ?? 6379,
        'password' => $_ENV['REDIS_PASSWORD'] ?? null,
    ],
    'database'           => [
        'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port'     => $_ENV['DB_PORT'] ?? 3306,
        'dbname'   => $_ENV['DB_NAME'] ?? 'rhapsody_db',
        'user'     => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset'  => 'utf8mb4',
    ],
    'mailer'             => [
        'transport'    => $_ENV['MAIL_TRANSPORT'] ?? 'smtp',
        'host'         => $_ENV['MAIL_HOST'] ?? 'localhost',
        'port'         => $_ENV['MAIL_PORT'] ?? 2525,
        'username'     => $_ENV['MAIL_USERNAME'] ?? null,
        'password'     => $_ENV['MAIL_PASSWORD'] ?? null,
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'hello@example.com',
        'from_name'    => $_ENV['MAIL_FROM_NAME'] ?? 'Example',
    ],
];
