<?php

namespace App\Controllers;

use App\Models\Roster;
use App\Models\Team;
use App\Models\TeamStrategy; // Added
use Core\BaseController;
use Core\Request;
use Core\Response;
use Core\Session;
use Twig\Environment;

class ClubhouseController extends BaseController
{
    protected Team $teamModel;
    protected Roster $rosterModel;
    protected TeamStrategy $strategyModel; // Added

    /**
     * @param Environment $twig
     */
    public function __construct( Environment $twig )
    {
        parent::__construct( $twig );
        $this->teamModel     = new Team();
        $this->rosterModel   = new Roster();
        $this->strategyModel = new TeamStrategy(); // Added
    }

    /**
     * @return mixed
     */
    public function index(): Response
    {
        $userTeamId = Session::get( 'user_team_id' );
        if ( !$userTeamId ) {
            return $this->redirect( '/new-game' );
        }

        $roster = $this->rosterModel->getPlayersByTeam( $userTeamId );

        // Fetch saved strategies to pre-fill the view
        $rotation = $this->strategyModel->getStrategy( $userTeamId, 'rotation' );
        $lineup   = $this->strategyModel->getStrategy( $userTeamId, 'lineup_rhp' ); // Default to RHP lineup

        return $this->view( 'game/clubhouse.twig', [
            'roster'         => $roster,
            'teamId'         => $userTeamId,
            'saved_rotation' => $rotation,
            'saved_lineup'   => $lineup,
        ] );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function saveStrategy( Request $request ): Response
    {
        $input      = json_decode( file_get_contents( 'php://input' ), true );
        $userTeamId = Session::get( 'user_team_id' );

        if ( !$userTeamId ) {
            return $this->json( ['error' => 'No team session'], 401 );
        }

        $rotation = $input['rotation'] ?? [];
        $lineup   = $input['lineup'] ?? [];

        $rSuccess = $this->strategyModel->saveStrategy( $userTeamId, 'rotation', $rotation );
        $lSuccess = $this->strategyModel->saveStrategy( $userTeamId, 'lineup_rhp', $lineup ); // Saving as RHP lineup for now

        if ( $rSuccess && $lSuccess ) {
            Session::flash( 'success', 'Lineups and Rotation saved successfully!' );
            return $this->json( ['success' => true] );
        }

        return $this->json( ['error' => 'Database save failed'], 500 );
    }
}
