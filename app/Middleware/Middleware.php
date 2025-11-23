<?php

namespace App\Middleware;

use Core\Request;

/**
 * The base class for all middleware.
 */
abstract class Middleware
{
    /**
     * Handles the middleware logic.
     * This method should be implemented by all child middleware classes.
     *
     * @param Request $request
     * @return void
     */
    abstract public function handle( Request $request ): void;
}
