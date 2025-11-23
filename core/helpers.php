<?php

use Core\RedirectResponse;

if ( !function_exists( 'redirect' ) )
{
    /**
     * Returns a new RedirectResponse instance.
     */
    function redirect( string $url ): RedirectResponse
    {
        $baseUrl = $_ENV['APP_BASE_URL'] ?? '';
        return new RedirectResponse( $baseUrl . $url );
    }
}
