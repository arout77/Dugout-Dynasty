<?php

namespace App\Controllers;

use App\Models\Roster;
use App\Models\Team;
use App\Models\TeamStrategy;
use App\Services\SimulationService;
use Core\BaseController;
use Core\Request;
use Core\Response;
use Core\Session;
use PDO;
use Twig\Environment;

class GameController extends BaseController
{
    protected SimulationService $simService;
    protected Team $teamModel;
    protected Roster $rosterModel;
    protected TeamStrategy $strategyModel;

    /**
     * @param Environment $twig
     */
    public function __construct( Environment $twig )
    {
        parent::__construct( $twig );
        $this->simService    = new SimulationService();
        $this->teamModel     = new Team();
        $this->rosterModel   = new Roster();
        $this->strategyModel = new TeamStrategy();
    }

    // ... (pregame, playBall methods unchanged) ...
    /**
     * @return mixed
     */
    public function pregame(): Response
    {
        $userTeamId = Session::get( 'user_team_id' );
        if ( !$userTeamId ) {
            return $this->redirect( '/draft' );
        }

        $userTeam = $this->teamModel->findById( $userTeamId );
        $db       = $this->teamModel->getDb();

        // 1. Fetch Next Game
        $sql = "SELECT g.*,
                       h.team_name as home_team,
                       a.team_name as away_team
                FROM games g
                JOIN teams h ON g.home_team_id = h.team_id
                JOIN teams a ON g.away_team_id = a.team_id
                WHERE (g.home_team_id = :uid1 OR g.away_team_id = :uid2)
                  AND g.status = 'scheduled'
                ORDER BY g.game_date ASC, g.game_number ASC
                LIMIT 1";

        $stmt = $db->prepare( $sql );
        $stmt->execute( [':uid1' => $userTeamId, ':uid2' => $userTeamId] );
        $nextGame = $stmt->fetch( PDO::FETCH_ASSOC );

        // 2. Fetch Upcoming Schedule
        $upcoming = [];
        if ( $nextGame ) {
            $sqlList = "SELECT g.*,
                               h.team_name as home_team,
                               a.team_name as away_team
                        FROM games g
                        JOIN teams h ON g.home_team_id = h.team_id
                        JOIN teams a ON g.away_team_id = a.team_id
                        WHERE (g.home_team_id = :uid1 OR g.away_team_id = :uid2)
                          AND g.status = 'scheduled'
                          AND (
                              g.game_date > :next_date1
                              OR (g.game_date = :next_date2 AND g.game_number > :next_num)
                          )
                        ORDER BY g.game_date ASC, g.game_number ASC
                        LIMIT 5";

            $stmtList = $db->prepare( $sqlList );

            $stmtList->execute( [
                ':uid1'       => $userTeamId,
                ':uid2'       => $userTeamId,
                ':next_date1' => $nextGame['game_date'],
                ':next_date2' => $nextGame['game_date'],
                ':next_num'   => $nextGame['game_number'],
            ] );

            $upcoming = $stmtList->fetchAll( PDO::FETCH_ASSOC );
        }

        return $this->view( 'game/pregame.twig', [
            'userTeam' => $userTeam,
            'nextGame' => $nextGame,
            'upcoming' => $upcoming,
            'isHome'   => $nextGame ? ( $nextGame['home_team_id'] == $userTeamId ) : false,
        ] );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function startGame( Request $request ): Response
    {
        $gameId     = $_REQUEST['game_id'] ?? null;
        $userTeamId = Session::get( 'user_team_id' );

        if ( !$gameId ) {
            Session::flash( 'error', 'No game selected.' );
            return $this->redirect( '/pregame' );
        }

        $db   = $this->teamModel->getDb();
        $stmt = $db->prepare( "SELECT * FROM games WHERE game_id = :id" );
        $stmt->execute( [':id' => $gameId] );
        $game = $stmt->fetch( PDO::FETCH_ASSOC );

        if ( !$game ) {
            Session::flash( 'error', 'Game not found.' );
            return $this->redirect( '/pregame' );
        }

        $homeId = $game['home_team_id'];
        $awayId = $game['away_team_id'];

        $homeLineup = $this->getLineupForGame( $homeId );
        $awayLineup = $this->getLineupForGame( $awayId );

        $state = [
            'game_id'              => $game['game_id'],
            'inning'               => 1,
            'half'                 => 'top',
            'outs'                 => 0,
            'balls'                => 0,
            'strikes'              => 0,
            'score'                => ['home' => 0, 'away' => 0],
            'bases'                => [null, null, null],
            'teams'                => [
                'home' => [
                    'id'      => $homeId,
                    'lineup'  => $homeLineup['lineup'],
                    'pitcher' => $homeLineup['pitcher'],
                    'name'    => $this->teamModel->findById( $homeId )['team_name'],
                ],
                'away' => [
                    'id'      => $awayId,
                    'lineup'  => $awayLineup['lineup'],
                    'pitcher' => $awayLineup['pitcher'],
                    'name'    => $this->teamModel->findById( $awayId )['team_name'],
                ],
            ],
            'current_batter_index' => ['home' => 0, 'away' => 0],
            'log'                  => ["Play Ball! Top of the 1st."],
        ];

        Session::set( 'game_state', $state );

        return $this->redirect( '/play-ball' );
    }

    /**
     * @return mixed
     */
    public function playBall(): Response
    {
        $state = Session::get( 'game_state' );
        if ( !$state ) {
            return $this->redirect( '/pregame' );
        }

        $battingTeamKey  = $state['half'] === 'top' ? 'away' : 'home';
        $pitchingTeamKey = $state['half'] === 'top' ? 'home' : 'away';

        $batterIdx     = $state['current_batter_index'][$battingTeamKey];
        $currentBatter = $state['teams'][$battingTeamKey]['lineup'][$batterIdx];
        // FIX: Correctly grab pitcher from defensive team
        $currentPitcher = $state['teams'][$pitchingTeamKey]['pitcher'];

        return $this->view( 'game/playball.twig', [
            'state'   => $state,
            'batter'  => $currentBatter,
            'pitcher' => $currentPitcher,
        ] );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function simAtBat( Request $request ): Response
    {
        $state  = Session::get( 'game_state' );
        $input  = json_decode( file_get_contents( 'php://input' ), true );
        $action = $input['action'] ?? 'normal';

        $battingTeamKey  = $state['half'] === 'top' ? 'away' : 'home';
        $pitchingTeamKey = $state['half'] === 'top' ? 'home' : 'away';

        $batterIdx = $state['current_batter_index'][$battingTeamKey];
        $batter    = $state['teams'][$battingTeamKey]['lineup'][$batterIdx];
        $pitcher   = $state['teams'][$pitchingTeamKey]['pitcher'];

        if ( $action === 'intentional_walk' ) {
            $result = ['event' => 'BB', 'desc' => 'Intentional Walk'];
        } elseif ( $action === 'bunt' ) {
            // Simplistic bunt logic
            $roll   = mt_rand( 0, 100 );
            $result = ( $roll < 70 ) ? ['event' => 'SAC', 'desc' => 'Sacrifice Bunt'] : ['event' => 'out', 'desc' => 'Failed Bunt'];
        } else {
            $result = $this->simService->simulateAtBat( $batter, $pitcher );
        }

        $this->processPlay( $state, $result, $batter['player_name'] );

        // FIX: Check if inning flipped. If so, the *new* batting team is up.
        // If inning did NOT flip (outs < 3), advance current batter.
        if ( $state['outs'] < 3 && $state['half'] === ( $battingTeamKey === 'away' ? 'top' : 'bottom' ) ) {
            $state['current_batter_index'][$battingTeamKey] = ( $batterIdx + 1 ) % 9;
        }
        // If inning flipped, processPlay already reset outs to 0 and toggled 'half'.
        // The frontend will reload the correct batter based on the new 'half'.

        Session::set( 'game_state', $state );

        // Return the NEXT batter for the UI update
        // Re-calculate based on potentially updated state
        $nextBattingKey  = $state['half'] === 'top' ? 'away' : 'home';
        $nextPitchingKey = $state['half'] === 'top' ? 'home' : 'away';

        $nextIdx     = $state['current_batter_index'][$nextBattingKey];
        $nextBatter  = $state['teams'][$nextBattingKey]['lineup'][$nextIdx];
        $nextPitcher = $state['teams'][$nextPitchingKey]['pitcher'];

        return $this->json( [
            'state'        => $state,
            'result'       => $result,
            'next_batter'  => $nextBatter,
            'next_pitcher' => $nextPitcher, // Send pitcher to update UI in case of change
        ] );
    }

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
        } elseif ( $result['event'] === 'HR' ) {
            $runs = 1;
            foreach ( $state['bases'] as $runner ) {
                if ( $runner ) {
                    $runs++;
                }
            }

            $state['bases'] = [null, null, null];

            $battingTeam = $state['half'] === 'top' ? 'away' : 'home';
            $state['score'][$battingTeam] += $runs;
            array_unshift( $state['log'], "HOMERUN! $batterName drives in $runs runs!" );
        } elseif ( $result['event'] === 'SAC' ) {
            $state['outs']++;
            $this->advanceRunners( $state, 1 );
            array_unshift( $state['log'], $desc );
        } else {
            $bases = 1;
            if ( $result['event'] === '2B' ) {
                $bases = 2;
            }

            if ( $result['event'] === '3B' ) {
                $bases = 3;
            }

            $runsScored = $this->advanceRunners( $state, $bases, true );

            if ( $runsScored > 0 ) {
                $battingTeam = $state['half'] === 'top' ? 'away' : 'home';
                $state['score'][$battingTeam] += $runsScored;
                $desc .= " ($runsScored run" . ( $runsScored > 1 ? 's' : '' ) . " score)";
            }
            array_unshift( $state['log'], $desc );
        }

        // FIX: Only switch sides if outs >= 3
        if ( $state['outs'] >= 3 ) {
            $this->switchSides( $state );
        }
    }

    /**
     * @param $state
     * @param $basesToAdvance
     * @param $batterReaches
     * @return mixed
     */
    private function advanceRunners( &$state, $basesToAdvance, $batterReaches = false )
    {
        $runs     = 0;
        $newBases = [null, null, null];

        // Runner on 3rd
        if ( $state['bases'][2] ) {
            $runs++; // Scores on any hit/walk (simplified)
        }
        // Runner on 2nd
        if ( $state['bases'][1] ) {
            if ( $basesToAdvance >= 2 ) {
                $runs++;
            } else {
                $newBases[2] = true;
            }
            // Move to 3rd on Single
        }
        // Runner on 1st
        if ( $state['bases'][0] ) {
            if ( $basesToAdvance >= 3 ) {
                $runs++;
            } else if ( $basesToAdvance == 2 ) {
                $newBases[2] = true;
            }
            // To 3rd on Double
            else {
                $newBases[1] = true;
            }
            // To 2nd on Single
        }

        if ( $batterReaches ) {
            if ( $basesToAdvance >= 4 ) {
                $runs++;
            } else if ( $basesToAdvance == 3 ) {
                $newBases[2] = true;
            } else if ( $basesToAdvance == 2 ) {
                $newBases[1] = true;
            } else {
                $newBases[0] = true;
            }

        }

        $state['bases'] = $newBases;
        return $runs;
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
            array_unshift( $state['log'], "--- End of Top " . $state['inning'] . " ---" );
        } else {
            $state['half'] = 'top';
            $state['inning']++;
            array_unshift( $state['log'], "--- End of Inning " . ( $state['inning'] - 1 ) . " ---" );
        }
    }

    /**
     * @param int $teamId
     */
    private function getLineupForGame( int $teamId ): array
    {
        $savedLineup   = $this->strategyModel->getStrategy( $teamId, 'lineup_rhp' );
        $savedRotation = $this->strategyModel->getStrategy( $teamId, 'rotation' );

        $roster  = $this->rosterModel->getPlayersByTeam( $teamId );
        $lineup  = [];
        $pitcher = null;

        if ( !empty( $savedRotation ) ) {
            $spId = $savedRotation[0]['player_id'];
            foreach ( $roster as $p ) {
                if ( $p['player_id'] == $spId ) {$pitcher = $p;
                    break;}
            }
        }
        if ( !$pitcher ) {
            $pitchers = array_filter( $roster, fn( $p ) => $p['position'] === 'SP' );
            $pitcher  = !empty( $pitchers ) ? reset( $pitchers ) : $roster[0];
        }

        if ( !empty( $savedLineup ) ) {
            foreach ( $savedLineup as $slot ) {
                foreach ( $roster as $p ) {
                    if ( $p['player_id'] == $slot['player_id'] ) {
                        $lineup[] = $p;
                        break;
                    }
                }
            }
        } else {
            $positions = ['C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'DH'];
            foreach ( $positions as $pos ) {
                $candidates = array_filter( $roster, fn( $p ) => $p['position'] === $pos );
                $player     = !empty( $candidates ) ? reset( $candidates ) : null;

                if ( !$player ) {
                    $others = array_filter( $roster, fn( $p ) => !in_array( $p['position'], ['SP', 'RP', 'P'] ) );
                    $player = reset( $others );
                }
                if ( $player ) {
                    $lineup[] = $player;
                }

            }
            while ( count( $lineup ) < 9 ) {
                $lineup[] = $roster[0];
            }

            $lineup = array_slice( $lineup, 0, 9 );
        }

        return ['lineup' => $lineup, 'pitcher' => $pitcher];
    }
}
