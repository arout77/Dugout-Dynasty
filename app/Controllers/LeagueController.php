<?php

namespace App\Controllers;

use App\Models\Team;
use App\Services\QuickSimService;
use Core\BaseController;
use Core\Response;
use Core\Session;

class LeagueController extends BaseController
{
    /**
     * @return mixed
     */
    public function simulateDay(): Response
    {
        $userTeamId = Session::get( 'user_team_id' );
        if ( !$userTeamId ) {
            return $this->redirect( '/dashboard' );
        }

        $db = ( new Team() )->getDb();

        // Find the user's current game date or next game date
        // We will simulate everything up to that date.
        $stmt = $db->prepare( "
            SELECT game_date FROM games
            WHERE (home_team_id = :uid1 OR away_team_id = :uid2)
            AND status = 'scheduled'
            ORDER BY game_date ASC LIMIT 1
        " );
        $stmt->execute( [
            ':uid1' => $userTeamId,
            ':uid2' => $userTeamId,
        ] );
        $nextDate = $stmt->fetchColumn();

        if ( !$nextDate ) {
            // Season over? Or just no scheduled games.
            Session::flash( 'error', 'No upcoming games found.' );
            return $this->redirect( '/dashboard' );
        }

        // Run Simulation
        $sim   = new QuickSimService();
        $count = $sim->simulateDay( $nextDate );

        Session::flash( 'success', "Simulated $count games for $nextDate." );
        return $this->redirect( '/dashboard' );
    }
}
