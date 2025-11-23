<?php

namespace App\Middleware;

use Core\Request;
use Core\Session;

class VerifyCsrfTokenMiddleware extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     * Wildcards (*) are supported.
     *
     * @var array
     */
    protected array $except = [
        'api/*', // Exclude all routes starting with 'api/'
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @return void
     */
    public function handle( Request $request ): void
    {
        if ( $this->isPostRequest( $request ) && !$this->inExceptArray( $request ) ) {
            $token = $request->get( '_token' );
            Session::verifyCsrfToken( $token );
        }
    }

    /**
     * Determine if the request is a POST request.
     *
     * @param Request $request
     * @return bool
     */
    protected function isPostRequest( Request $request ): bool
    {
        return $request->getMethod() === 'POST';
    }

    /**
     * Determine if the request URI is in the exception array.
     *
     * @param Request $request
     * @return bool
     */
    protected function inExceptArray( Request $request ): bool
    {
        foreach ( $this->except as $except ) {
            // The request's `is` method handles wildcard matching
            if ( $request->is( $except ) ) {
                return true;
            }
        }

        return false;
    }
}
