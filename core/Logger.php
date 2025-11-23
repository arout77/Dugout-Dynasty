<?php

namespace Core;

class Logger
{
    protected string $path;

    /**
     * @param string $path The absolute path to the log file.
     */
    public function __construct( string $path )
    {
        $this->path = $path;
    }

    /**
     * Writes a message to the log file.
     *
     * @param string $message The message to log.
     * @param string $level The log level (e.g., INFO, ERROR, DEBUG).
     */
    public function log( string $message, string $level = 'INFO' ): void
    {
        // Ensure the directory for the log file exists.
        $directory = dirname( $this->path );
        if ( !is_dir( $directory ) ) {
            mkdir( $directory, 0777, true );
        }

        $formattedMessage = sprintf(
            "[%s] %s: %s" . PHP_EOL,
            date( 'Y-m-d H:i:s' ), // Timestamp
            strtoupper( $level ), // Log level
            $message // The actual message
        );

        // Append the message to the file.
        file_put_contents( $this->path, $formattedMessage, FILE_APPEND );
    }

    /**
     * Reads the last N lines from the log file.
     *
     * @param int $lines The number of lines to read from the end of the file.
     * @return string The log content or an error message.
     */
    public function read( int $lines = 50 ): string
    {
        if ( empty( $this->path ) || !file_exists( $this->path ) || !is_readable( $this->path ) ) {
            return "Log file not found or not readable at: " . htmlspecialchars( $this->path );
        }

        try {
            $file = new \SplFileObject( $this->path, 'r' );
            $file->seek( PHP_INT_MAX ); // Go to the end of the file
            $last_line = $file->key();

            $iterator = new \LimitIterator( $file, ( $last_line > $lines ? $last_line - $lines : 0 ), $last_line );

            return htmlspecialchars( implode( "", iterator_to_array( $iterator ) ), ENT_QUOTES, 'UTF-8' );
        } catch ( \Exception $e ) {
            return "Error reading log file: " . htmlspecialchars( $e->getMessage() );
        }
    }
}
