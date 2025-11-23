<?php

namespace Core;

class Request
{
    private readonly array $getParams;
    private readonly array $postParams;
    private readonly array $cookies;
    private readonly array $files;
    private readonly array $server;
    public readonly string $uri;

    public function __construct()
    {
        $this->getParams  = $_GET;
        $this->postParams = $_POST;
        $this->cookies    = $_COOKIE;
        $this->files      = $_FILES;
        $this->server     = $_SERVER;
        $this->uri        = $_SERVER['REQUEST_URI'] ?? '/';
    }

    public function getMethod(): string
    {
        return strtolower( $this->server['REQUEST_METHOD'] ?? 'get' );
    }

    /**
     * UPDATED: This method now correctly uses the APP_BASE_URL from .env
     * to determine the clean application path for the router.
     */
    public function getPath(): string
    {
        $path     = $this->server['REQUEST_URI'] ?? '/';
        $position = strpos( $path, '?' );
        if ( $position !== false ) {
            $path = substr( $path, 0, $position );
        }

        // --- START OF FIX ---
        // Get the base URL from the environment config
        $baseUrl = $_ENV['APP_BASE_URL'] ?? '';

        // Check if the path starts with the base URL and remove it
        if ( !empty( $baseUrl ) && $baseUrl !== '/' && str_starts_with( $path, $baseUrl ) ) {
            $path = substr( $path, strlen( $baseUrl ) );
        }
        // --- END OF FIX ---

        if ( strlen( $path ) > 1 ) {
            $path = rtrim( $path, '/' );
        }
        return empty( $path ) ? '/' : $path;
    }

    /**
     * --- As of v1.0.1 ---
     * Fixes issue with JSON payloads not being included in $_POST
     * Gets the request body, supporting both traditional form data
     * and JSON payloads.
     *
     * @return array
     */
    public function getBody(): array
    {
        if ( $this->getMethod() !== 'post' ) {
            return [];
        }

        // 1. Check for a JSON content-type header first.
        if ( isset( $this->server['CONTENT_TYPE'] ) && str_contains( strtolower( $this->server['CONTENT_TYPE'] ), 'application/json' ) ) {
            $json = file_get_contents( 'php://input' );
            return json_decode( $json, true ) ?? [];
        }

        // 2. If no JSON header, check standard $_POST data (for forms).
        if ( !empty( $this->postParams ) ) {
            $body = [];
            foreach ( $this->postParams as $key => $value ) {
                $body[$key] = filter_input( INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS );
            }
            return $body;
        }

        // 3. FALLBACK: If $_POST is empty, it might be JSON sent without
        // the correct header. Try to parse it anyway.
        $json = file_get_contents( 'php://input' );
        if ( !empty( $json ) ) {
            $data = json_decode( $json, true );
            if ( is_array( $data ) ) {
                return $data;
            }
        }

        // 4. If all else fails, return an empty array.
        return [];
    }

    /**
     * @param string $key
     * @param $default
     * @return mixed
     */
    public function get( string $key, $default = null )
    {
        return $this->getParams[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param $default
     * @return mixed
     */
    public function post( string $key, $default = null )
    {
        return $this->postParams[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param $default
     */
    public function getQueryParam( string $key, $default = null )
    {
        return filter_input( INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS ) ?? $default;
    }

    /**
     * @return mixed
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Get a value from the request (GET, POST, or COOKIE).
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input( string $key, $default = null )
    {
        // Check $_REQUEST, but fallback to default if not found
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Get all request data (GET + POST).
     *
     * @return array
     */
    public function all(): array
    {
        // We specifically merge $_GET and $_POST to avoid including $_COOKIE
        // which $_REQUEST often contains by default.
        return array_merge( $_GET, $_POST );
    }

    /**
     * Check if the request method is POST.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
}
