<?php

namespace Core;

class Route
{
    /**
     * The captured parameters from a matched dynamic route.
     * @var array
     */
    protected array $params = [];

    protected ?string $middleware = null;

    public function __construct(
        protected string $method,
        protected string $path,
        protected mixed $callback
    ) {
    }

    /**
     * A "magic" method used by var_export() for route caching.
     * It allows PHP to reconstruct the Route object from the cache file.
     *
     * @param array $properties An array of properties to set on the new object.
     * @return self A new instance of the Route class.
     */
    public static function __set_state( array $properties ): self
    {
        $route             = new self( $properties['method'], $properties['path'], $properties['callback'] );
        $route->middleware = $properties['middleware'] ?? null;
        return $route;
    }

    /**
     * Checks if this route matches the given request method and path.
     * It now supports dynamic segments like /posts/{id}.
     *
     * @param string $method The request's method.
     * @param string $uri The request's URI.
     *
     * @return bool True if the route matches, false otherwise.
     */
    public function matches( string $method, string $uri ): bool
    {
        // First, check if the HTTP method matches.
        if ( strtolower( $this->method ) !== strtolower( $method ) ) {
            return false;
        }

        // Convert the route path into a regular expression.
        // 1. Replace dynamic segments {param} with a regex capture group.
        $pattern = preg_replace( '/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $this->path );

        // 2. Escape forward slashes and anchor the pattern.
        $pattern = "~^" . $pattern . "$~";

        // 3. Attempt to match the URI against the pattern.
        if ( preg_match( $pattern, $uri, $matches ) ) {
            // Remove the full match from the beginning of the array.
            array_shift( $matches );
            // Store the captured parameter values.
            $this->params = $matches;
            return true;
        }

        return false;
    }

    /**
     * Gets the HTTP method for this route.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Gets the path for this route.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Gets the callback action for this route.
     * @return mixed
     */
    public function getCallback(): mixed
    {
        return $this->callback;
    }

    /**
     * Gets the captured URL parameters.
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Assigns a middleware key to this route.
     *
     * @param string $key The middleware key (e.g., 'auth', 'guest').
     * @return self
     */
    public function middleware( string $key ): self
    {
        $this->middleware = $key;
        return $this; // Return self to allow chaining
    }

    /**
     * @return mixed
     */
    public function getMiddleware(): ?string
    {
        return $this->middleware;
    }
}
