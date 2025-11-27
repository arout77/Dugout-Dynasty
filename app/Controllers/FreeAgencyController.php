<?php

namespace App\Controllers;

use App\Models\Hitter;
use App\Models\Pitcher;
use App\Models\Roster;
use App\Models\Team;
use Core\BaseController;
use Core\Request;
use Core\Response;
use Core\Session;

class FreeAgencyController extends BaseController
{
    protected Roster $rosterModel;
    protected Team $teamModel;
    protected Hitter $hitterModel;
    protected Pitcher $pitcherModel;

    public function __construct()
    {
        $this->rosterModel  = new Roster();
        $this->teamModel    = new Team();
        $this->hitterModel  = new Hitter();
        $this->pitcherModel = new Pitcher();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index( Request $request )
    {
        $userTeamId = Session::get( 'user_team_id' );
        if ( !$userTeamId ) {
            return $this->redirect( '/dashboard' );
        }

        $page   = (int) ( $request->input( 'page' ) ?? 1 );
        $type   = $request->input( 'type' ) ?? 'hitters'; // 'hitters' or 'pitchers'
        $limit  = 20;
        $offset = ( $page - 1 ) * $limit;

        // Fetch Available Players (Not in Roster table)
        // We need a method in Hitter/Pitcher models for this.
        // Assuming findAvailable exists or we create it.

        $players = [];
        $total   = 0;

        if ( $type === 'pitchers' ) {
            // Logic to find undrafted pitchers
            // SQL: SELECT * FROM pitchers WHERE row_id NOT IN (SELECT player_id FROM rosters WHERE stats_source='pitchers')
            $players = $this->getUndraftedPitchers( $limit, $offset );
        } else {
            $players = $this->getUndraftedHitters( $limit, $offset );
        }

        $team        = $this->teamModel->findById( $userTeamId );
        $rosterCount = $this->rosterModel->getCountByTeam( $userTeamId );

        return $this->view( 'game/free_agency.twig', [
            'players'     => $players,
            'type'        => $type,
            'page'        => $page,
            'team'        => $team,
            'rosterCount' => $rosterCount,
        ] );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function signPlayer( Request $request )
    {
        $userTeamId = Session::get( 'user_team_id' );
        $playerId   = $request->input( 'player_id' );
        $type       = $request->input( 'type' ); // 'hitter' or 'pitcher'
        $salary     = 500000; // Minimum salary for now, or dynamic

        if ( !$userTeamId || !$playerId ) {
            Session::flash( 'error', 'Invalid request.' );
            return $this->redirect( '/free-agency' );
        }

        // 1. Check Roster Size
        $count = $this->rosterModel->getCountByTeam( $userTeamId );
        if ( $count >= 40 ) { // MLB Reserve limit
            Session::flash( 'error', 'Roster full (40 players). Drop someone first.' );
            return $this->redirect( '/free-agency' );
        }

        // 2. Fetch Player Data
        $player = null;
        if ( $type === 'pitcher' ) {
            $player = $this->pitcherModel->findByRowId( $playerId );
        } else {
            $player = $this->hitterModel->findByRowId( $playerId );
        }

        if ( !$player ) {
            Session::flash( 'error', 'Player not found.' );
            return $this->redirect( '/free-agency' );
        }

        // 3. Add to Roster
        // We use the existing addPlayer method
        $this->rosterModel->addPlayer( $userTeamId, $player, $type === 'pitcher' ? 'pitcher' : 'hitter' );

        Session::flash( 'success', "Signed {$player['NAME']}!" );
        return $this->redirect( '/free-agency' );
    }

    // Helpers for finding undrafted (Should ideally be in Model, but putting here for speed)
    /**
     * @param $limit
     * @param $offset
     * @return mixed
     */
    private function getUndraftedHitters( $limit, $offset )
    {
        $db  = $this->teamModel->getDb();
        $sql = "SELECT *, 'hitter' as type FROM hitters
                WHERE row_id NOT IN (SELECT player_id FROM rosters WHERE stats_source='hitters')
                ORDER BY (H/AB) DESC  -- Sort by AVG roughly
                LIMIT $limit OFFSET $offset";
        return $db->query( $sql )->fetchAll( \PDO::FETCH_ASSOC );
    }

    /**
     * @param $limit
     * @param $offset
     * @return mixed
     */
    private function getUndraftedPitchers( $limit, $offset )
    {
        $db = $this->teamModel->getDb();
        // Sort by Wins or ERA proxy
        $sql = "SELECT *, 'pitcher' as type FROM pitchers
                WHERE row_id NOT IN (SELECT player_id FROM rosters WHERE stats_source='pitchers')
                ORDER BY W DESC
                LIMIT $limit OFFSET $offset";
        return $db->query( $sql )->fetchAll( \PDO::FETCH_ASSOC );
    }
}
