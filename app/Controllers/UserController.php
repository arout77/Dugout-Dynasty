<?php

namespace App\Controllers;

use App\Models\User;
use Core\BaseController;
use Core\Pagination;
use Core\Request;
use Core\Response;

class UserController extends BaseController
{
    use Twig\Environment;
    /**
     * @param Environment $twig
     */
    public function __construct( Environment $twig )
    {
        parent::__construct( $twig );
    }

    /**
     * @return mixed
     */
    public function showUsers(): Response
    {
        $request = new Request;

        // 1. Create an instance of our User model
        $userModel = new \App\Models\User();

        // --- Pagination configuration
        // 1. Define the allowed options for items per page.
        $allowedLimits = [5, 10, 25, 50];
        $defaultLimit  = 10;
        // 2. Get the user's selection from the URL, default to 10.
        $usersPerPage = (int) $request->getQueryParam( 'limit', $defaultLimit );

        // 3. Ensure the selected limit is a valid one.
        if ( !in_array( $usersPerPage, $allowedLimits ) )
        {
            $usersPerPage = $defaultLimit;
        }

        // 1. Get the current page from the URL, default to 1
        $currentPage = (int) $request->getQueryParam( 'page', 1 );

        // 2. Get the total number of users
        $totalusers = $userModel->countAll();

        // 3. Create the Pagination object
        $pagination = new \Core\Pagination( $totalusers, $usersPerPage, $currentPage );

        // 4. Fetch only the users for the current page
        $users = $userModel->findPaginated( $pagination->getLimit(), $pagination->getOffset() );

        // 5. Pass both the users and the pagination object to the view
        return $this->view( 'home/users.twig', [
            'users'         => $users,
            'pagination'    => $pagination,
            'allowedLimits' => $allowedLimits,
        ] );
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public function viewUser( Request $request, string $user_id ): Response
    {
        $userModel = new User();

        // Sanitize the incoming user_id
        $uid = preg_replace( "/[^a-zA-Z0-9]/", "", $user_id );

        $userData = $userModel->getUserById( $uid );

        // We need a view for this! Let's create a placeholder for now.
        // You'll need to create the file: views/users/user.twig
        return $this->view( 'users/user.twig', [
            'user' => $userData,
        ] );
    }

    /**
     * @return mixed
     */
    public function showRegisterForm(): Response
    {
        // Show the form for the first time
        return $this->view( 'register.twig', [
            'old'    => [],
            'errors' => [],
        ] );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function register( Request $request ): Response
    {
        $postData = $request->getBody();
        $fileData = $request->getFiles();
        // We combine post and file data to validate them together
        $data = array_merge( $postData, $fileData );

        $validator = new Validator();
        $rules     = [
            'username'   => 'required|alpha_num|min:3|max:20',
            'email'      => 'required|email',
            'password'   => 'required|min:8|confirmed',
            'website'    => 'url',
            'birth_date' => 'required|date_format:Y-m-d',
            'role'       => 'required|in:user,editor',
            'avatar'     => 'required|image|mimes:jpeg,png',
        ];

        if ( $validator->validate( $data, $rules ) )
        {
            // Validation passed!
            // Here you would hash the password and save the user to the database.
            // You would also move the uploaded file from the temp directory.
            $response = new Response();
            $response->setContent( "<h1>Registration Successful!</h1><p>Welcome, {$data['username']}!</p>" );
            return $response;
        }
        else
        {
            // Validation failed, show the form again with errors.
            return $this->view( 'register.twig', [
                'errors' => $validator->getErrors(),
                'old'    => $postData,
            ] );
        }
    }
}
