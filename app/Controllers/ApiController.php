<?php

namespace App\Controllers;

use App\Models\User;
use Core\BaseController;
use Core\Request;
use Core\Response;

/**
 * Handles all API requests for the application.
 */
class ApiController extends BaseController
{
    /**
     * Returns a list of all users as JSON.
     */
    public function getUsers(): Response
    {
        $userModel = new User();
        $users     = $userModel->findAll();

        return $this->json( $users );
    }

    /**
     * Returns a single user by their ID, or a 404 error if not found.
     */
    public function getUser( Request $request, string $userId ): Response
    {
        $userModel = new User();
        $user      = $userModel->getUserById( $userId );

        if ( !$user )
        {
            return $this->json( ['error' => 'User not found.'], 404 );
        }

        return $this->json( $user );
    }
}
