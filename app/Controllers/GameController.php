<?php

namespace App\Controllers;

use App\Models\Roster;
use App\Models\Team;
use App\Services\LeagueStatsService;
use App\Services\ScheduleService;
use App\Services\SimulationService;
use Core\BaseController;
use Core\Request;
use Core\Response;
use Core\Session;
use Twig\Environment;

class GameController extends BaseController
{
    protected SimulationService $simService;
    protected Team $teamModel;
    protected Roster $rosterModel;
    protected ScheduleService $scheduleService;

    /**
     * @param Environment $twig
     */
    public function __construct( Environment $twig )
    {
        parent::__construct( $twig );
        $this->scheduleService = new ScheduleService();
        $this->simService      = new SimulationService();
        $this->teamModel       = new Team();
        $this->rosterModel     = new Roster();
    }

    /**
     * Pre-Game Screen: Choose Opponent & Lineups
     */
    public function pregame(): Response
    {
        $userTeamId = Session::get( 'user_team_id' );
        if ( !$userTeamId ) {
            return $this->redirect( '/draft' );
        }

        // Get User's Team & Potential Opponents
        $userTeam  = $this->teamModel->findById( $userTeamId );
        $opponents = $this->teamModel->execute( "SELECT * FROM teams WHERE team_id != ?", [$userTeamId] )->fetchAll();

        // Get User's Roster for Lineup Selection
        $roster = $this->rosterModel->getPlayersByTeam( $userTeamId );

        return $this->view( 'game/pregame.twig', [
            'userTeam'  => $userTeam,
            'opponents' => $opponents,
            'roster'    => $roster,
        ] );
    }

    /**
     * Initialize a New Game
     */
    public function startGame( Request $request ): Response
    {
        $opponentId = $request->input( 'opponent_id' );
        $userTeamId = Session::get( 'user_team_id' );

        // 1. Load Rosters
        // For MVP, we auto-generate lineups based on position
        $homeLineup = $this->generateLineup( $userTeamId ); // User is Home for now
        $awayLineup = $this->generateLineup( $opponentId );

        // 2. Create Game State
        $state = [
            'inning'               => 1,
            'half'                 => 'top', // Visitors bat first
            'outs'  => 0,
            'balls'                => 0,
            'strikes'              => 0,
            'score'                => ['home' => 0, 'away' => 0],
            'bases'                => [null, null, null], // 1B, 2B, 3B
            'teams' => [
                'home' => ['id' => $userTeamId, 'lineup' => $homeLineup, 'pitcher' => $homeLineup['pitcher']],
                'away' => ['id' => $opponentId, 'lineup' => $awayLineup, 'pitcher' => $awayLineup['pitcher']],
            ],
            'current_batter_index' => ['home' => 0, 'away' => 0],
            'log'                  => ["Play Ball! Top of the 1st."],
        ];

        Session::set( 'game_state', $state );

        return $this->redirect( '/play-ball' );
    }

    /**
     * Main Game Interface
     */
    public function playBall(): Response
    {
        $state = Session::get( 'game_state' );
        if ( !$state ) {
            return $this->redirect( '/pregame' );
        }

        // Resolve current matchup details for display
        $battingTeamKey  = $state['half'] === 'top' ? 'away' : 'home';
        $pitchingTeamKey = $state['half'] === 'top' ? 'home' : 'away';

        $batterIdx      = $state['current_batter_index'][$battingTeamKey];
        $currentBatter  = $state['teams'][$battingTeamKey]['lineup'][$batterIdx];
        $currentPitcher = $state['teams'][$pitchingTeamKey]['pitcher'];

        return $this->view( 'game/playball.twig', [
            'state'   => $state,
            'batter'  => $currentBatter,
            'pitcher' => $currentPitcher,
        ] );
    }

    /**
     * API: Simulates one At-Bat
     */
    public function simAtBat(): Response
    {
        $state = Session::get( 'game_state' );

        // Identify Batter & Pitcher
        $battingTeamKey  = $state['half'] === 'top' ? 'away' : 'home';
        $pitchingTeamKey = $state['half'] === 'top' ? 'home' : 'away';

        $batterIdx = $state['current_batter_index'][$battingTeamKey];
        $batter    = $state['teams'][$battingTeamKey]['lineup'][$batterIdx];
        $pitcher   = $state['teams'][$pitchingTeamKey]['pitcher'];

        // Run Simulation
        $result = $this->simService->simulateAtBat( $batter, $pitcher );

        // Process Result & Update State (Advance runners, score runs, record outs)
        $this->processPlay( $state, $result, $batter['player_name'] );

        // Advance Lineup
        if ( $result['event'] !== 'out' || $state['outs'] < 3 ) {
            $state['current_batter_index'][$battingTeamKey] = ( $batterIdx + 1 ) % 9;
        }

        Session::set( 'game_state', $state );

        return $this->json( [
            'state'       => $state,
            'result'      => $result,
            'next_batter' => $state['teams'][$battingTeamKey]['lineup'][$state['current_batter_index'][$battingTeamKey]],
        ] );
    }

    // --- Helpers ---

    /**
     * @param $state
     * @param array $result
     * @param string $batterName
     */
    private function processPlay( array &$state, array $result, string $batterName )
    {
        $desc = "{$batterName}: " . $result['desc'];

        if ( $result['event'] === 'SO' || $result['event'] === 'out' ) {
            $state['outs']++;
            array_unshift( $state['log'], $desc );

            if ( $state['outs'] >= 3 ) {
                $this->switchSides( $state );
            }
        } elseif ( $result['event'] === 'HR' ) {
            // Count runs: Batter + anyone on base
            $runs = 1;
            foreach ( $state['bases'] as $runner ) {
                if ( $runner ) {
                    $runs++;
                }
            }

            // Clear bases
            $state['bases'] = [null, null, null];

            // Add Score
            $battingTeam = $state['half'] === 'top' ? 'away' : 'home';
            $state['score'][$battingTeam] += $runs;

            array_unshift( $state['log'], "HOMERUN! $batterName drives in $runs runs!" );
        } else {
            // Handle Hits/Walks (Advancing runners logic needed here)
            // Simplified: Just put runner on first for now
            // Real logic needs to push existing runners
            $this->advanceRunners( $state, $result['event'] );
            array_unshift( $state['log'], $desc );
        }
    }

    /**
     * @param $state
     * @param $hitType
     */
    private function advanceRunners( &$state, $hitType )
    {
        // Placeholder for base advancement logic
        // E.g. if 1B, shift everyone 1 base. If 2B, shift 2.
        // Check for scoring.
    }

    /**
     * @param $state
     */
    private function switchSides( array &$state )
    {
        $state['outs']  = 0;
        $state['bases'] = [null, null, null];

        if ( $state['half'] === 'top' ) {
            $state['half'] = 'bottom';
            array_unshift( $state['log'], "End of Top " . $state['inning'] );
        } else {
            $state['half'] = 'top';
            $state['inning']++;
            array_unshift( $state['log'], "End of Inning " . ( $state['inning'] - 1 ) );
        }
    }

    /**
     * @param int $teamId
     */
    private function generateLineup( int $teamId ): array
    {
        // Simple heuristic: Get best player at each position
        $roster    = $this->rosterModel->getPlayersByTeam( $teamId );
        $lineup    = [];
        $positions = ['C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'DH']; // Assuming DH for simplicity

        // Pitcher
        $pitchers = array_filter( $roster, fn( $p ) => $p['position'] === 'SP' );
        $pitcher  = !empty( $pitchers ) ? reset( $pitchers ) : $roster[0];

        foreach ( $positions as $pos ) {
            $candidates = array_filter( $roster, fn( $p ) => $p['position'] === $pos );
            $player     = !empty( $candidates ) ? reset( $candidates ) : null;

            // Fallback to any hitter
            if ( !$player ) {
                $others = array_filter( $roster, fn( $p ) => !in_array( $p['position'], ['SP', 'RP', 'P'] ) );
                $player = reset( $others );
            }

            if ( $player ) {
                $lineup[] = $player;
            }

        }

        // Ensure 9 batters
        $lineup = array_slice( $lineup, 0, 9 );

        return [
            'lineup' => $lineup, // Array of 9 players
            'pitcher' => $pitcher,
        ];
    }
}
