<?php

namespace Core;

/**
 * Represents a complete HTTP response, including status code, headers, and content.
 */
class Response
{
    /**
     * The HTTP status code. Defaults to 200 (OK).
     * @var int
     */
    protected int $statusCode = 200;

    /**
     * A key-value array of HTTP headers.
     * @var array
     */
    protected array $headers = [];

    /**
     * The response body content.
     * @var string
     */
    protected string $content = '';

    /**
     * Sets the HTTP status code for the response.
     *
     * @param int $code The HTTP status code (e.g., 200, 404, 500).
     * @return self
     */
    public function setStatusCode( int $code ): self
    {
        $this->statusCode = $code;
        return $this; // Return self for method chaining
    }

    /**
     * Adds a header to the response.
     *
     * @param string $name  The header name (e.g., 'Content-Type').
     * @param string $value The header value (e.g., 'application/json').
     * @return self
     */
    public function setHeader( string $name, string $value ): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Sets the body content for the response.
     *
     * @param string $content The HTML or string content to be sent.
     * @return self
     */
    public function setContent( string $content ): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * --- THIS IS THE NEW METHOD ---
     * Gets the body content of the response.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Gets the HTTP status code of the response.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Gets all headers set for the response.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Assembles and sends the final HTTP response to the client.
     * This is the last thing that should happen in a request.
     */
    public function send(): void
    {
        // 1. Send the status code
        http_response_code( $this->statusCode );

        // 2. Send all registered headers
        foreach ( $this->headers as $name => $value ) {
            header( "{$name}: {$value}" );
        }

        // 3. Send the content body
        echo $this->content;
    }
}
