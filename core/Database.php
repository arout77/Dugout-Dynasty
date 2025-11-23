<?php

namespace Core;

use PDO;
use PDOException;

/**
 * A Singleton class for managing the database connection.
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {}
    private function __clone()
    {}
    // In core/Database.php

    public static function getInstance(): PDO
    {
        if ( self::$instance === null ) {
            $config   = require __DIR__ . '/../config.php';
            $dbConfig = $config['database'];

            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                // REVERT to using the $config array for the check
                if ( ( $config['app_env'] ?? 'production' ) === 'development' ) {
                    self::$instance = new TraceablePDO( $dsn, $dbConfig['user'], $dbConfig['password'], $options );
                } else {
                    self::$instance = new PDO( $dsn, $dbConfig['user'], $dbConfig['password'], $options );
                }
            } catch ( PDOException $e ) {
                throw new PDOException( $e->getMessage(), (int) $e->getCode() );
            }
        }

        return self::$instance;
    }
}
