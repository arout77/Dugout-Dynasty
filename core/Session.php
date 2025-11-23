<?php

namespace Core;

/**
 * A secure, static wrapper for handling PHP sessions.
 *
 * This class provides a consistent and safe interface for managing session data,
 * including user authentication state, flash messages, and CSRF tokens.
 */
class Session
{
    /**
     * Starts the session if it has not already been started.
     * This should be called once at the beginning of every request.
     */
    public static function start(): void
    {
        if ( session_status() === PHP_SESSION_NONE )
        {
            session_start();
        }
    }

    /**
     * Sets a value in the session.
     *
     * @param string $key The key to store the value under.
     * @param mixed $value The value to be stored.
     */
    public static function set( string $key, $value ): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieves a value from the session by its key.
     *
     * @param string $key The key of the item to retrieve.
     * @param mixed|null $default A default value to return if the key is not found.
     * @return mixed
     */
    public static function get( string $key, $default = null )
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Checks if a key exists in the session.
     */
    public static function has( string $key ): bool
    {
        return isset( $_SESSION[$key] );
    }

    /**
     * Removes a value from the session by its key.
     */
    public static function remove( string $key ): void
    {
        unset( $_SESSION[$key] );
    }

    /**
     * Destroys the entire session, logging the user out.
     */
    public static function destroy(): void
    {
        session_destroy();
    }

    /**
     * Regenerates the session ID to prevent session fixation attacks.
     * This should be called immediately after a user logs in.
     */
    public static function regenerate(): void
    {
        session_regenerate_id( true );
    }

    /**
     * Writes session data to disk and closes the session.
     * This is crucial to call before any redirects to ensure data is saved.
     */
    public static function close(): void
    {
        session_write_close();
    }

    /**
     * Sets a flash message that will be removed after the next request.
     */
    public static function flash( string $key, string $message ): void
    {
        self::set( 'flash_' . $key, $message );
    }

    /**
     * Retrieves a flash message and then removes it from the session.
     */
    public static function getFlash( string $key, $default = null ): ?string
    {
        $message = self::get( 'flash_' . $key, $default );
        self::remove( 'flash_' . $key );
        return $message;
    }

    /**
     * Checks if a flash message exists for a given key.
     */
    public static function hasFlash( string $key ): bool
    {
        return self::has( 'flash_' . $key );
    }

    /**
     * Generates and returns a CSRF token, creating one if it doesn't exist.
     */
    public static function csrfToken(): string
    {
        if ( !self::has( '_token' ) )
        {
            $token = bin2hex( random_bytes( 32 ) );
            self::set( '_token', $token );
        }
        return self::get( '_token' );
    }

    /**
     * Verifies a submitted CSRF token against the one stored in the session.
     * Uses hash_equals for timing-attack-safe comparison.
     */
    public static function verifyCsrfToken( string $token ): bool
    {
        return self::has( '_token' ) && hash_equals( self::get( '_token' ), $token );
    }
}
