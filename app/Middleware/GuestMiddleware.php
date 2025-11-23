<?php

namespace App\Middleware;

use Core\Request;
use Core\Session;

class GuestMiddleware extends Middleware
{
    /**
     * @param Request $request
     */
    public function handle( Request $request ): void
    {
        // If the user is already logged in...
        if ( Session::has( 'user_id' ) )
        {
            // ...redirect them to their dashboard and stop execution.
            header( 'Location: ' . getenv( 'APP_BASE_URL' ) . '/dashboard' );
            exit();
        }
    }
}
