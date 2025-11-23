<?php

namespace Core;

class RedirectResponse extends Response
{
    protected string $url;

    /**
     * @param string $url
     */
    public function __construct( string $url )
    {
        $this->url = $url;
        $this->setStatusCode( 302 ); // 302 Found is a standard redirect code
    }

    /**
     * Attaches a flash message to the session before redirecting.
     */
    public function with( string $key, string $message ): self
    {
        Session::flash( $key, $message );
        return $this;
    }

    /**
     * Overrides the parent send method to handle the redirect.
     */
    public function send(): void
    {
        Session::close(); // Ensure session is saved before redirecting
        header( 'Location: ' . $this->url );
        exit();
    }
}
