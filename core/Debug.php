<?php

namespace Core;

use Core\QueryLogger;
use Doctrine\DBAL\Logging\DebugStack;

/**
 * A simple data collector for the developer toolbar.
 * Uses a singleton pattern to be accessible anywhere during the request.
 */
class Debug
{
    private static ?self $instance = null;
    private array $data            = [];
    private float $startTime;
    private int $startMemory;

    private function __construct()
    {}
    public static function getInstance(): self
    {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Starts the timer and records initial memory usage.
     * Should be called at the very beginning of the request.
     */
    public function start(): void
    {
        $this->startTime           = microtime( true );
        $this->startMemory         = memory_get_usage();
        $this->data['php_version'] = phpversion();
    }

    /**
     * Gathers final data points at the end of the request.
     */
    public function end( Response $response, array $config, Container $container, Route $route = null ): void
    {
        $this->data['execution_time'] = round( ( microtime( true ) - $this->startTime ) * 1000, 2 );
        $this->data['memory_usage']   = round( ( memory_get_peak_usage() - $this->startMemory ) / 1024 / 1024, 2 );
        $this->data['response_code']  = $response->getStatusCode();
        $this->data['app_version']    = $config['app_version'] ?? 'N/A';
        $this->data['session']        = $_SESSION ?? [];

        $doctrineQueries = [];
        if ( $container->has( QueryLogger::class ) ) { // <-- Use QueryLogger
            $doctrineQueries = $container->resolve( QueryLogger::class )->queries;
        }

        $pdoQueries            = TraceablePDO::getQueryLog();
        $this->data['queries'] = array_merge( $doctrineQueries, $pdoQueries );

        // Get Doctrine Queries
        if ( $container->has( DebugStack::class ) ) {
            $this->data['queries'] = $container->resolve( DebugStack::class )->queries;
        }

        // Use the new Logger class to read the logs
        $phpLogger    = new Logger( $config['logging']['php_error_log_path'] ?? '' );
        $apacheLogger = new Logger( $config['logging']['apache_error_log_path'] ?? '' );

        $this->data['logs'] = [
            'php'    => $phpLogger->read( 50 ),
            'apache' => $apacheLogger->read( 50 ),
        ];

        if ( $route ) {
            $callback = $route->getCallback();
            if ( is_array( $callback ) && count( $callback ) === 2 ) {
                $controller          = explode( '\\', $callback[0] );
                $this->data['route'] = [
                    'method'     => $route->getMethod(),
                    'path'       => $route->getPath(),
                    'controller' => end( $controller ),
                    'action'     => $callback[1],
                ];
            }
        }
    }

    /**
     * @return mixed
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Reads the last 50 lines of a log file.
     */
    private function readLogFile( string $path ): string
    {
        if ( empty( $path ) || !file_exists( $path ) || !is_readable( $path ) ) {
            return "Log file not found or not readable at: " . htmlspecialchars( $path );
        }
        // Use a memory-efficient way to read the end of a large file
        $file = new \SplFileObject( $path, 'r' );
        $file->seek( PHP_INT_MAX );
        $last_line = $file->key();
        $lines     = new \LimitIterator( $file, ( $last_line > 50 ? $last_line - 50 : 0 ), $last_line );
        return htmlspecialchars( implode( "", iterator_to_array( $lines ) ), ENT_QUOTES, 'UTF-8' );
    }
}
