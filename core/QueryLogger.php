<?php

namespace Core;

use Doctrine\DBAL\Logging\SQLLogger;

/**
 * A simple SQL logger that counts and times queries.
 * This is used by the Debug Toolbar.
 */
class QueryLogger implements SQLLogger
{
    public array $queries     = [];
    private float $start_time = 0;

    /**
     * @param $sql
     * @param array $params
     * @param array $types
     */
    public function startQuery( $sql, ?array $params = null, ?array $types = null )
    {
        $this->start_time = microtime( true );
        $this->queries[]  = [
            'sql'         => $sql,
            'params'      => $params,
            'types'       => $types,
            'executionMS' => 0, // <-- RENAMED from execution_time
            'caller' => $this->findQueryCaller(), // <-- ADDED this line
        ];
    }

    public function stopQuery()
    {
        $last_query_key = array_key_last( $this->queries );
        if ( $last_query_key !== null ) {
            $this->queries[$last_query_key]['executionMS'] = microtime( true ) - $this->start_time; // <-- RENAMED from execution_time
        }
    }

    /**
     * Finds the file and line that initiated the query.
     * Copied from TraceablePDO to ensure consistent logging.
     */
    private function findQueryCaller(): ?array
    {
        $trace       = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
        $projectRoot = dirname( __DIR__, 2 );

        foreach ( $trace as $entry ) {
            if ( !isset( $entry['file'] ) ) {
                continue;
            }

            $file = str_replace( '\\', '/', $entry['file'] );

            // If the file is outside our project root, skip it
            if ( strpos( $file, str_replace( '\\', '/', $projectRoot ) ) === false ) {
                continue;
            }

            // If the file is one of our core DB/Logger classes, skip it
            $filename = basename( $file );
            if ( $filename === 'QueryLogger.php' || $filename === 'TraceablePDO.php' || $filename === 'Database.php' ) {
                continue;
            }

            // Also skip if it's from within the Doctrine vendor directory
            if ( strpos( $file, '/vendor/doctrine/' ) !== false ) {
                continue;
            }

            // The first file that is not a core class is our caller
            return [
                'file' => str_replace( str_replace( '\\', '/', $projectRoot ) . '/', '', $file ),
                'line' => $entry['line'],
            ];
        }
        return null;
    }

    public function __destruct()
    {
        // This is a failsafe. If a query is started but never stopped
        // (e.g., due to an exception), we'll mark its time as 'unfinished'.
        $last_query_key = array_key_last( $this->queries );

        if ( $last_query_key !== null ) {
            // Use a direct isset() check to avoid the "temporary expression" error.
            if ( isset( $this->queries[$last_query_key]['executionMS'] ) && $this->queries[$last_query_key]['executionMS'] === 0 ) {
                $this->queries[$last_query_key]['executionMS'] = 'unfinished';
            }
        }
    }
}
