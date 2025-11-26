<?php

namespace App\Controllers;

use App\Events\UserRegistered;
use App\Models\User;
use Core\BaseController;
use Core\Container;
use Core\Events\EventDispatcher;
use Core\Request;
use Core\Response;
use Core\Session;

class AuthController extends BaseController
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): Response
    {
        return $this->view( 'auth/login.twig' );
    }

    /**
     * Show the registration form.
     */
    public function showRegisterForm(): Response
    {
        return $this->view( 'auth/register.twig' );
    }

    /**
     * Handle the login request.
     */
    public function login( Request $request ): Response
    {
        $email    = $request->input( 'email' );
        $password = $request->input( 'password' );

        $userModel = new User();
        $user      = $userModel->findByEmail( $email );

        if ( $user && password_verify( $password, $user['password'] ) ) {
            Session::set( 'user_id', $user['user_id'] );
            Session::set( 'user_team_id', $user['user_team_id'] );
            // Session::flash( 'success', 'Welcome back!' );
            // FIX: Ensure we capture the team_id if it exists
            if ( !empty( $user['user_team_id'] ) ) {
                Session::set( 'user_team_id', $user['user_team_id'] );
            }
            return redirect( '/dashboard' ); // Redirect to game dashboard
        }

        Session::flash( 'error', 'Invalid credentials.' );
        return redirect( '/login' );
    }

    /**
     * Handle the logout request.
     */
    public function logout(): Response
    {
        Session::remove( 'user_id' );
        Session::destroy();
        return redirect( '/' );
    }

    /**
     * Handle the registration request.
     * Now with Graceful Error Handling!
     */
    public function register( Request $request ): Response
    {
        $data = $request->all();

        // 1. Validation
        if ( empty( $data['name'] ) || empty( $data['email'] ) || empty( $data['password'] ) ) {
            Session::flash( 'error', 'All fields are required.' );
            return redirect( '/register' );
        }

        $userModel = new User();

        // 2. Graceful Check: Does email already exist?
        // This prevents the most common cause of the crash.
        if ( $userModel->findByEmail( $data['email'] ) ) {
            Session::flash( 'error', 'That email address is already registered. Please login.' );
            return redirect( '/register' );
        }

        // 3. Creation Attempt wrapped in Try-Catch
        try {
            if ( $userModel->create( $data ) ) {
                // Success! Fetch the new user
                $user = $userModel->findByEmail( $data['email'] );

                // Dispatch Event (Safe check for helper)
                // We assume the 'event()' helper exists in your framework based on typical usage
                if ( function_exists( 'event' ) ) {
                    event( new UserRegistered( $user ) );
                }

                // Log the user in immediately
                Session::set( 'user_id', $user['user_id'] );
                Session::flash( 'success', 'Registration successful! Welcome to Dugout Dynasty.' );

                return redirect( '/draft' );
            } else {
                Session::flash( 'error', 'Registration failed due to a database error. Please try again.' );
                return redirect( '/register' );
            }
        } catch ( \Exception $e ) {
            // 4. Catch-All for unexpected crashes
            // This catches Unique Constraint violations on other columns (like Name)
            // or connection timeouts.

            // Check for "Duplicate entry" string in SQL error
            if ( strpos( $e->getMessage(), 'Duplicate entry' ) !== false ) {
                Session::flash( 'error', 'An account with those details (Name or Email) already exists.' );
            } else {
                // Log the actual error for the developer, show generic to user
                error_log( $e->getMessage() );
                Session::flash( 'error', 'An unexpected error occurred. Please try again later.' );
            }

            return redirect( '/register' );
        }
    }
}
