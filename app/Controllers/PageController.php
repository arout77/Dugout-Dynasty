<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Models\Team;
// use App\Models\User;
use Core\BaseController;
use Core\FileUploader;
use Core\Mailer;
use Core\Pagination;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Validator;
use PDO;
use Twig\Environment;

class PageController extends BaseController
{
    /**
     * @param Environment $twig
     */
    public function __construct( Environment $twig )
    {
        parent::__construct( $twig );
        $this->teamModel = new Team();
    }

    /**
     * Show the home page.
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->view( 'home/welcome.twig' );
    }

    /**
     * @return mixed
     */
    public function dashboard()
    {
        $userId = Session::get( 'user_id' );
        if ( !$userId ) {
            return $this->redirect( '/login' );
        }

        // 1. Get User Team
        $userTeamId = Session::get( 'user_team_id' );
        if ( !$userTeamId ) {
            $db = $this->teamModel->getDb();
            // FIX: Use 'user_team_id' based on your user schema
            $stmt = $db->prepare( "SELECT user_team_id FROM users WHERE id = :uid" );
            $stmt->execute( [':uid' => $userId] );
            $userTeamId = $stmt->fetchColumn();

            if ( $userTeamId ) {
                Session::set( 'user_team_id', $userTeamId );
            } else {
                return $this->redirect( '/new-game' );
            }
        }

        $userTeam = $this->teamModel->findById( $userTeamId );
        $db       = $this->teamModel->getDb();

        $userLeagueName = $userTeam['league_name'] ?? 'American League';

        // 2. Get Next Game (Fixed Params)
        $sqlNext = "SELECT g.*, h.team_name as home_name, a.team_name as away_name
                    FROM games g
                    JOIN teams h ON g.home_team_id = h.team_id
                    JOIN teams a ON g.away_team_id = a.team_id
                    WHERE (g.home_team_id = :uid1 OR g.away_team_id = :uid2)
                      AND g.status = 'scheduled'
                    ORDER BY g.game_date ASC, g.game_number ASC LIMIT 1";
        $stmtNext = $db->prepare( $sqlNext );
        $stmtNext->execute( [
            ':uid1' => $userTeamId,
            ':uid2' => $userTeamId,
        ] );
        $nextGame = $stmtNext->fetch( PDO::FETCH_ASSOC );

        // 3. Get Standings (Single League View)
        $sqlRank = "SELECT team_id, team_name, w, l, (w / NULLIF(w+l, 0)) as pct
                    FROM teams
                    WHERE league_name = :lname
                    ORDER BY w DESC, pct DESC";
        $stmtRank = $db->prepare( $sqlRank );
        $stmtRank->execute( [':lname' => $userLeagueName] );
        $standings = $stmtRank->fetchAll( PDO::FETCH_ASSOC );

        $myRank = '-';
        foreach ( $standings as $index => $t ) {
            if ( $t['team_id'] == $userTeamId ) {
                $myRank = $index + 1;
                break;
            }
        }

        // 4. Get League Leaders (FIXED: JOIN rosters for names)
        $leaders = [];

        // Helper that joins 'rosters' to get player_name
        // Note: We join on player_id AND team_id to ensure uniqueness in current season
        $fetchLeader = function ( $sql ) use ( $db, $userLeagueName ) {
            $stmt = $db->prepare( $sql );
            $stmt->execute( [':lname' => $userLeagueName] );
            return $stmt->fetchAll( PDO::FETCH_ASSOC );
        };

        $leaders['avg'] = $fetchLeader( "
            SELECT r.player_name, t.team_name, (p.H / p.AB) as val
            FROM player_season_stats p
            JOIN teams t ON p.team_id = t.team_id
            JOIN rosters r ON p.player_id = r.player_id AND p.team_id = r.team_id
            WHERE p.AB > 10 AND t.league_name = :lname
            ORDER BY val DESC LIMIT 5
        " );

        $leaders['hr'] = $fetchLeader( "
            SELECT r.player_name, t.team_name, p.HR as val
            FROM player_season_stats p
            JOIN teams t ON p.team_id = t.team_id
            JOIN rosters r ON p.player_id = r.player_id AND p.team_id = r.team_id
            WHERE t.league_name = :lname
            ORDER BY val DESC LIMIT 5
        " );

        $leaders['era'] = $fetchLeader( "
            SELECT r.player_name, t.team_name, (p.ER * 9 / p.IP) as val
            FROM pitcher_season_stats p
            JOIN teams t ON p.team_id = t.team_id
            JOIN rosters r ON p.player_id = r.player_id AND p.team_id = r.team_id
            WHERE p.IP > 5 AND t.league_name = :lname
            ORDER BY val ASC LIMIT 5
        " );

        $leaders['wins'] = $fetchLeader( "
            SELECT r.player_name, t.team_name, p.W as val
            FROM pitcher_season_stats p
            JOIN teams t ON p.team_id = t.team_id
            JOIN rosters r ON p.player_id = r.player_id AND p.team_id = r.team_id
            WHERE t.league_name = :lname
            ORDER BY val DESC LIMIT 5
        " );

        return $this->view( 'home/dashboard.twig', [
            'userTeam'   => $userTeam,
            'nextGame'   => $nextGame,
            'rank'       => $myRank,
            'totalTeams' => count( $standings ),
            'standings'  => array_slice( $standings, 0, 5 ),
            'leagueName' => $userLeagueName,
            'leaders'    => $leaders,
        ] );
    }

    /**
     * Show the about page.
     *
     * @return Response
     */
    public function about(): Response
    {
        // 1. Create a new Response object
        $response = new Response();

        // 2. Use the correct setContent() method
        $response->setContent( "<h1>About Us</h1><p>This page is working!</p>" );

        // 3. Return the configured response
        return $response;
    }

    /**
     * Shows a dynamic page based on a URL parameter.
     *
     * @param string $slug The value captured from the URL (e.g., 'my-first-post').
     * @return Response
     */
    public function showPost( string $slug ): Response
    {
        $response = new Response();

        // Sanitize the output to prevent XSS attacks
        $safeSlug = htmlspecialchars( $slug, ENT_QUOTES, 'UTF-8' );

        $response->setContent( "<h1>Viewing Post</h1><p>The slug is: <strong>{$safeSlug}</strong></p>" );

        return $response;
    }

    /**
     * @return mixed
     */
    public function showUsers(): Response
    {
        $request = new Request;

        // 1. Create an instance of our User model
        $userModel = new \App\Models\User();

        // 1. Define the allowed options for items per page.
        $allowedLimits = [5, 10, 25, 50];
        $defaultLimit  = 10;

        // 2. Get the user's selection from the URL, default to 10.
        $usersPerPage = (int) $request->getQueryParam( 'limit', $defaultLimit );

        // 3. Ensure the selected limit is a valid one.
        if ( !in_array( $usersPerPage, $allowedLimits ) ) {
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
        $uid       = preg_replace( "/[^a-zA-Z0-9]/", "", $user_id );
        $userData  = $userModel->getUserById( $uid );

        if ( !$userData ) {
            // Handle user not found, maybe redirect or show a 404
            return redirect( '/users' )->with( 'error', 'User not found.' );
        }

        // --- DEFINE THE SEO META DATA ---
        $meta = [
            'title'       => $userData['name'] . ' - User Profile | Rhapsody',
            'description' => 'View the profile for ' . $userData['name'] . '. Learn more about their contributions on the Rhapsody platform.',
        ];

        return $this->view( 'users/user.twig', ['user' => $userData], $meta );
    }

    /**
     * THIS METHOD DISPLAYS THE FORM
     * It corresponds to the Router::get('/contact', ...) route.
     * This is the method that was missing.
     */
    public function contact(): Response
    {
        // This just renders the Twig template with the form.
        return $this->view( 'contact/contact.twig' );
    }

    /**
     * THIS METHOD HANDLES THE SUBMISSION
     * It corresponds to the Router::post('/contact', ...) route.
     */
    public function handleContact( Request $request ): Response
    {
        $data      = $request->getBody();
        $validator = new Validator();
        $rules     = [
            'name'    => 'required|min:2|max:50',
            'email'   => 'required|email',
            'message' => 'required|min:10|max:1000',
        ];

        if ( $validator->validate( $data, $rules ) ) {
            // Validation Passed
            $mailer   = new Mailer();
            $to       = $_ENV['MAIL_FROM_ADDRESS'] ?? 'admin@example.com';
            $subject  = "New Contact Form Submission from {$data['name']}";
            $htmlBody = "<p>Name: {$data['name']}</p><p>Email: {$data['email']}</p><p>Message: {$data['message']}</p>";

            try {
                $mailer->send( $to, $subject, $htmlBody );
                Session::flash( 'success', 'Your message has been sent successfully!' );
            } catch ( \Exception $e ) {
                error_log( $e->getMessage() ); // Log the actual error
                Session::flash( 'error', 'Sorry, we could not send your message at this time.' );
            }

            // --- THIS IS THE CRITICAL FIX ---
            // 1. Force the session data to be written to disk.
            Session::close();

            // 2. NOW issue the redirect.
            header( 'Location: ' . ( $_ENV['APP_BASE_URL'] ?? '' ) . '/contact' );
            exit();
            // --- END FIX ---

        } else {
            // Validation Failed
            return $this->view( 'contact/contact.twig', [
                'errors' => $validator->getErrors(),
                'old'    => $data,
            ] );
        }
    }

    /**
     * @return mixed
     */
    public function showRegisterForm(): Response
    {
        return $this->view( 'register.twig', ['old' => [], 'errors' => []] );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function register( Request $request ): Response
    {
        // ... (register method remains the same)
        $postData = $request->getBody();
        $fileData = $request->getFiles();
        $data     = array_merge( $postData, $fileData );

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

        if ( $validator->validate( $data, $rules ) ) {
            $response = new Response();
            $response->setContent( "<h1>Registration Successful!</h1><p>Welcome, {$data['username']}!</p>" );
            return $response;
        } else {
            return $this->view( 'register.twig', [
                'errors' => $validator->getErrors(),
                'old'    => $postData,
            ] );
        }
    }

    /**
     * @return mixed
     */
    public function showUploadForm(): Response
    {
        return $this->view( 'upload.twig' );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function handleUpload( Request $request ): Response
    {
        $uploader = new FileUploader();
        $uploader->setAllowedMimes( ['image/jpeg', 'image/png', 'application/pdf'] )
                 ->setMaxSize( 5 * 1024 * 1024 ); // 5 MB

        if ( $uploader->handle( 'documents' ) ) {
            return $this->json( [
                'success' => true,
                'files'   => $uploader->getUploadedFiles(),
            ] );
        } else {
            return $this->json( [
                'success' => false,
                'errors'  => $uploader->getErrors(),
            ], 400 );
        }
    }
}
