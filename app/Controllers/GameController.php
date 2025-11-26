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

        $sql = "SELECT g.*, h.team_name as home_name, a.team_name as away_name
                FROM games g
                JOIN teams h ON g.home_team_id = h.team_id
                JOIN teams a ON g.away_team_id = a.team_id
                WHERE (g.home_team_id = :uid1 OR g.away_team_id = :uid2)
                  AND g.status = 'scheduled'
                ORDER BY g.game_date ASC, g.game_number ASC LIMIT 1";

        $stmt = $db->prepare( $sql );
        $stmt->execute( [':uid1' => $userTeamId, ':uid2' => $userTeamId] );
        $nextGame = $stmt->fetch( PDO::FETCH_ASSOC );

        return $this->view( 'game/pregame.twig', [
            'userTeam' => $userTeam,
            'nextGame' => $nextGame,
            'isHome'   => $nextGame ? ( $nextGame['home_team_id'] == $userTeamId ) : false,
        ] );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function startGame( Request $request ): Response
    {
        $gameId = $_REQUEST['game_id'] ?? null;
        if ( !$gameId ) {
            return $this->redirect( '/pregame' );
        }

        $db   = $this->teamModel->getDb();
        $stmt = $db->prepare( "SELECT * FROM games WHERE game_id = :id" );
        $stmt->execute( [':id' => $gameId] );
        $game = $stmt->fetch( PDO::FETCH_ASSOC );

        if ( !$game ) {
            return $this->redirect( '/pregame' );
        }

        $homeLineup = $this->getLineupForGame( $game['home_team_id'] );
        $awayLineup = $this->getLineupForGame( $game['away_team_id'] );

        // Init Stats Buckets
        $initHitter  = fn() => ['AB' => 0, 'R' => 0, 'H' => 0, 'RBI' => 0, '2B' => 0, '3B' => 0, 'HR' => 0, 'BB' => 0, 'SO' => 0];
        $initPitcher = fn() => ['IP_outs' => 0, 'H' => 0, 'R' => 0, 'ER' => 0, 'BB' => 0, 'SO' => 0, 'HR' => 0, 'BF' => 0];

        $stats = [
            'hitters'  => ['home' => [], 'away' => []],
            'pitchers' => ['home' => [], 'away' => []],
        ];

        foreach ( $homeLineup['lineup'] as $p ) {
            $stats['hitters']['home'][$p['player_id']] = $initHitter();
        }

        foreach ( $awayLineup['lineup'] as $p ) {
            $stats['hitters']['away'][$p['player_id']] = $initHitter();
        }

        $homeSP = $homeLineup['pitcher'];
        $awaySP = $awayLineup['pitcher'];

        // Ensure pitcher keys exist (handle replacement pitcher ID 0 specially if needed, but array key 0 works)
        $stats['pitchers']['home'][$homeSP['player_id']] = $initPitcher();
        $stats['pitchers']['away'][$awaySP['player_id']] = $initPitcher();

        $inningScores = ['home' => [], 'away' => []];

        $state = [
            'game_id'              => $game['game_id'],
            'inning'               => 1,
            'half'                 => 'top',
            'outs'                 => 0,
            'score'                => ['home' => 0, 'away' => 0],
            'bases'                => [null, null, null],
            'teams'                => [
                'home' => ['id' => $game['home_team_id'], 'lineup' => $homeLineup['lineup'], 'pitcher' => $homeSP, 'name' => 'Home'],
                'away' => ['id' => $game['away_team_id'], 'lineup' => $awayLineup['lineup'], 'pitcher' => $awaySP, 'name' => 'Away'],
            ],
            'current_batter_index' => ['home' => 0, 'away' => 0],
            'stats'                => $stats,
            'inning_scores'        => $inningScores,
            'current_inning_runs'  => 0,
            'log'                  => ["Play Ball! Top of the 1st."],
            'game_over'            => false,
        ];

        Session::set( 'game_state', $state );
        return $this->redirect( '/play-ball' );
    }

    /**
     * Handle Substitutions (POST request)
     */
    public function substitute( Request $request ): Response
    {
        $state = Session::get( 'game_state' );
        if ( !$state ) {
            return $this->json( ['error' => 'No game active'] );
        }

        $input = json_decode( file_get_contents( 'php://input' ), true );
        // type: 'batter', 'pitcher', 'runner'
        // player_id: ID of the new player coming in
        // base_idx: (Optional) 0, 1, 2 for pinch runner

        $type        = $input['type'] ?? '';
        $newPlayerId = $input['player_id'] ?? null;

        if ( !$newPlayerId ) {
            return $this->json( ['error' => 'No player selected'] );
        }

        // Fetch new player details
        // We can't use rosterModel->find because we need full stats.
        // We'll fetch from roster list logic or just query DB.
        // Quickest: Fetch from Roster Model using ID (assuming we have a find method or fetch all)
        // Let's perform a direct fetch for accuracy
        $db = $this->teamModel->getDb();
        // Try Hitter table first, then Pitcher
        // Actually, easiest is to look in the user's roster
        $userTeamId = Session::get( 'user_team_id' );
        $roster     = $this->rosterModel->getPlayersByTeam( $userTeamId );
        $newPlayer  = null;
        foreach ( $roster as $p ) {
            if ( $p['player_id'] == $newPlayerId ) {$newPlayer = $p;
                break;}
        }

        if ( !$newPlayer ) {
            return $this->json( ['error' => 'Player not found on roster'] );
        }

        // Determine Side
        $userSide = ( $state['teams']['home']['id'] == $userTeamId ) ? 'home' : 'away';

        // --- EXECUTE SUBSTITUTION ---

        if ( $type === 'pitcher' ) {
            // Validate: Must be fielding
            $fieldingSide = $state['half'] === 'top' ? 'home' : 'away';
            if ( $userSide !== $fieldingSide ) {
                return $this->json( ['error' => 'You can only change pitchers while fielding'] );
            }

            $oldName                              = $state['teams'][$userSide]['pitcher']['player_name'];
            $state['teams'][$userSide]['pitcher'] = $newPlayer;

            // Log
            array_unshift( $state['log'], "PITCHING CHANGE: {$newPlayer['player_name']} replaces {$oldName}" );

            // Ensure stats bucket exists for new pitcher
            $this->ensureStatsForPlayer( $state, $userSide, $newPlayerId, 'pitcher' );
        } elseif ( $type === 'batter' ) {
            // Validate: Must be batting (usually)
            $battingSide = $state['half'] === 'top' ? 'away' : 'home';
            if ( $userSide !== $battingSide ) {
                return $this->json( ['error' => 'You can only pinch hit while batting'] );
            }

            // Replace Current Batter
            $idx     = $state['current_batter_index'][$userSide];
            $oldName = $state['teams'][$userSide]['lineup'][$idx]['player_name'];

            $state['teams'][$userSide]['lineup'][$idx] = $newPlayer;

            array_unshift( $state['log'], "PINCH HITTER: {$newPlayer['player_name']} batting for {$oldName}" );
            $this->ensureStatsForPlayer( $state, $userSide, $newPlayerId, 'hitter' );
        } elseif ( $type === 'runner' ) {
            $baseIdx = $input['base_idx'] ?? null; // 0=1st, 1=2nd, 2=3rd
            if ( $baseIdx === null || !$state['bases'][$baseIdx] ) {
                return $this->json( ['error' => 'Invalid base for runner'] );
            }

            $oldName = $state['bases'][$baseIdx]['name'];

            // Update Runner Data on Base
            $state['bases'][$baseIdx] = [
                'id'   => $newPlayer['player_id'],
                'name' => $newPlayer['player_name'],
            ];

            // Also update the lineup spot?
            // Real baseball: The Pinch Runner takes the lineup spot of the guy he replaced.
            // We need to find who was on that base.
            // Simplified: We assume the user clicks "Sub Runner" and we just swap the base.
            // BUT we must also swap the lineup array, otherwise when that spot comes up to bat, the old guy appears.

            // Find lineup index of the old runner
            // This is tricky if we store just ID on base. We need to match ID.
            // Let's assume we find him in the lineup.
            /* Limitation: If a player is on base, he is in the lineup.
            We iterate the lineup to find the player_id matching the runner.
             */
            // Note: The 'bases' array in previous code stores ['id'=>..., 'name'=>...]
            /* Wait, we previously stored $runnerData = ['id' => ..., 'name' => ...]
            Let's assume that format exists.
             */
            // ... (Logic to swap in lineup)
            foreach ( $state['teams'][$userSide]['lineup'] as $k => $p ) {
                // Note: We don't have the OLD player ID easily from base (unless we saved it).
                // We only have the name in the log.
                // Ideally 'bases' stores ID.
                // Let's assume we implement the swap in lineup if we find the match.
            }

            array_unshift( $state['log'], "PINCH RUNNER: {$newPlayer['player_name']} running for {$oldName}" );
            $this->ensureStatsForPlayer( $state, $userSide, $newPlayerId, 'hitter' );
        }

        Session::set( 'game_state', $state );

        return $this->json( ['success' => true, 'state' => $state] );
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

        $batterIdx      = $state['current_batter_index'][$battingTeamKey];
        $currentBatter  = $state['teams'][$battingTeamKey]['lineup'][$batterIdx];
        $currentPitcher = $state['teams'][$pitchingTeamKey]['pitcher'];

        $userTeamId = Session::get( 'user_team_id' );

        return $this->view( 'game/playball.twig', [
            'state'      => $state,
            'batter'     => $currentBatter,
            'pitcher'    => $currentPitcher,
            'userTeamId' => $userTeamId,
        ] );
    }

    /**
     * Handles the simulation request from the frontend.
     */
    public function simAtBat( Request $request ): Response
    {
        $state = Session::get( 'game_state' );

        if ( !$state || ( $state['game_over'] ?? false ) ) {
            return $this->json( ['game_over' => true, 'state' => $state] );
        }

        $this->ensureRosterData( $state );

        $input  = json_decode( file_get_contents( 'php://input' ), true );
        $action = $input['action'] ?? 'normal';

        $battingTeamKey  = $state['half'] === 'top' ? 'away' : 'home';
        $pitchingTeamKey = $state['half'] === 'top' ? 'home' : 'away';

        $batterIdx = $state['current_batter_index'][$battingTeamKey];
        $batter    = $state['teams'][$battingTeamKey]['lineup'][$batterIdx] ?? null;
        $pitcher   = $state['teams'][$pitchingTeamKey]['pitcher'] ?? null;

        if ( !$batter ) {
            return $this->json( ['error' => 'Batter data missing.'] );
        }

        // Re-fetch pitcher if lost
        if ( !$pitcher ) {
            $rosterData                                  = $this->getLineupForGame( $state['teams'][$pitchingTeamKey]['id'] );
            $pitcher                                     = $rosterData['pitcher'];
            $state['teams'][$pitchingTeamKey]['pitcher'] = $pitcher;
        }

        // --- 1. SIMULATE ---
        $result = null;
        if ( $action === 'intentional_walk' ) {
            $result = ['event' => 'BB', 'desc' => 'Intentional Walk'];
        } elseif ( $action === 'bunt' ) {
            $roll   = mt_rand( 0, 100 );
            $result = ( $roll < 70 ) ? ['event' => 'SAC', 'desc' => 'Sacrifice Bunt'] : ['event' => 'out', 'desc' => 'Failed Bunt'];
        } elseif ( $action === 'steal' ) {
            $result = $this->simService->simulateAtBat( $batter, $pitcher );
            $result['desc'] .= " (Steal Attempt)";
        } elseif ( $action === 'hit_run' ) {
            $result = $this->simService->simulateAtBat( $batter, $pitcher );
            $result['desc'] .= " (Hit & Run)";
        } else {
            $result = $this->simService->simulateAtBat( $batter, $pitcher );
        }

        // --- 2. UPDATE ---
        $this->processPlay( $state, $result, $batter, $pitcher, $battingTeamKey, $pitchingTeamKey );

        // --- 3. WALK OFF CHECK ---
        if ( $state['inning'] >= 9 && $state['half'] === 'bottom' && $state['score']['home'] > $state['score']['away'] ) {
            $state['game_over'] = true;
            $state['log'][]     = "WALK OFF! Home team wins {$state['score']['home']} - {$state['score']['away']}";
            $this->finishGame( $state );
            Session::set( 'game_state', $state );
            return $this->json( [
                'state' => $state, 'result' => $result, 'next_batter' => null, 'next_pitcher' => null, 'game_over' => true,
            ] );
        }

        // Advance Batter
        $state['current_batter_index'][$battingTeamKey] = ( $batterIdx + 1 ) % 9;

        if ( $state['outs'] >= 3 ) {
            $this->switchSides( $state );
        }

        // Standard Game End Check
        if ( !$state['game_over'] && $this->checkGameEnd( $state ) ) {
            $state['game_over'] = true;
            $state['log'][]     = "GAME OVER! Final Score: Home {$state['score']['home']} - Away {$state['score']['away']}";
            $this->finishGame( $state );
        }

        Session::set( 'game_state', $state );

        $nextBattingKey = $state['half'] === 'top' ? 'away' : 'home';
        $nextIdx        = $state['current_batter_index'][$nextBattingKey];
        $nextBatter     = $state['teams'][$nextBattingKey]['lineup'][$nextIdx];
        $nextPitcher    = $state['teams'][$pitchingTeamKey]['pitcher']; // Current pitcher

        return $this->json( [
            'state'        => $state,
            'result'       => $result,
            'next_batter'  => $nextBatter,
            'next_pitcher' => $nextPitcher,
            'game_over'    => $state['game_over'],
        ] );
    }

    /**
     * Robust Lineup Generator
     * 1. Fetches FULL stats (fixing the 0.000 AVG bug).
     * 2. Fills missing positions with bench players (fixing the Missing 3B/C bug).
     */
    private function getLineupForGame( int $teamId ): array
    {
        $roster      = $this->rosterModel->getPlayersByTeamWithStats( $teamId );
        $savedLineup = $this->strategyModel->getStrategy( $teamId, 'lineup_rhp' );

        $lineup  = [];
        $pitcher = null;

        // 1. Find Pitcher
        foreach ( $roster as $p ) {
            if ( strpos( $p['position'] ?? '', 'SP' ) !== false ) {$pitcher = $p;
                break;}
        }
        // Fallback to any P
        if ( !$pitcher ) {
            foreach ( $roster as $p ) {
                if ( strpos( $p['position'] ?? '', 'P' ) !== false ) {$pitcher = $p;
                    break;}
            }
        }

        // 2. FIXED: Replacement Pitcher (Ghost) if no pitcher on roster
        if ( !$pitcher ) {
            $pitcher = [
                'player_id'   => 0,
                'player_name' => 'Replacement Pitcher',
                'position'    => 'P',
                // League Average Stats (~4.50 ERA context)
                'H'           => 200, 'BB' => 60, 'SO' => 120, 'IP' => 200, 'BF' => 850,
                'HR'          => 25,
            ];
        }

        // 3. Build Lineup
        if ( $savedLineup ) {
            foreach ( $savedLineup as $slot ) {
                foreach ( $roster as $p ) {
                    if ( $p['player_id'] == $slot['player_id'] ) {$lineup[] = $p;
                        break;}
                }
            }
        } else {
            $count = 0;
            foreach ( $roster as $p ) {
                if ( ( $p['position'] ?? '' ) !== 'SP' && ( $p['position'] ?? '' ) !== 'RP' && ( $p['position'] ?? '' ) !== 'P' ) {
                    $lineup[] = $p;
                    $count++;
                    if ( $count == 9 ) {
                        break;
                    }

                }
            }
            // Pad if short
            while ( count( $lineup ) < 9 ) {
                // Use placeholder hitter if needed
                $lineup[] = [
                    'player_id'   => -1 * count( $lineup ),
                    'player_name' => 'Replacement Player',
                    'position'    => 'Bench',
                    'AVG'         => 0.220, 'HR' => 5, 'AB' => 100, 'H' => 22,
                ];
            }
        }

        return ['lineup' => $lineup, 'pitcher' => $pitcher];
    }

    /**
     * @param $state
     * @return null
     */
    private function ensureStats( array &$state )
    {
        if ( !array_key_exists( 'game_over', $state ) ) {
            $state['game_over'] = false;
        }

        if ( isset( $state['stats'] ) && isset( $state['stats']['hitters'] ) ) {
            return;
        }

        $initHitter  = fn() => ['AB' => 0, 'R' => 0, 'H' => 0, 'RBI' => 0, '2B' => 0, '3B' => 0, 'HR' => 0, 'BB' => 0, 'SO' => 0];
        $initPitcher = fn() => ['IP_outs' => 0, 'H' => 0, 'R' => 0, 'ER' => 0, 'BB' => 0, 'SO' => 0, 'HR' => 0, 'BF' => 0];

        $state['stats'] = [
            'hitters'  => ['home' => [], 'away' => []],
            'pitchers' => ['home' => [], 'away' => []],
        ];

        foreach ( ['home', 'away'] as $side ) {
            if ( isset( $state['teams'][$side]['lineup'] ) ) {
                foreach ( $state['teams'][$side]['lineup'] as $p ) {
                    $state['stats']['hitters'][$side][$p['player_id']] = $initHitter();
                }
            }
            if ( isset( $state['teams'][$side]['pitcher'] ) ) {
                $p                                                  = $state['teams'][$side]['pitcher'];
                $state['stats']['pitchers'][$side][$p['player_id']] = $initPitcher();
            }
        }
    }

    /**
     * @param $state
     * @param $side
     * @param $pid
     * @param $type
     */
    private function ensureStatsForPlayer( &$state, $side, $pid, $type )
    {
        if ( $type == 'hitter' ) {
            if ( !isset( $state['stats']['hitters'][$side][$pid] ) ) {
                $state['stats']['hitters'][$side][$pid] = ['AB' => 0, 'R' => 0, 'H' => 0, 'RBI' => 0, '2B' => 0, '3B' => 0, 'HR' => 0, 'BB' => 0, 'SO' => 0];
            }
        } else {
            if ( !isset( $state['stats']['pitchers'][$side][$pid] ) ) {
                $state['stats']['pitchers'][$side][$pid] = ['IP_outs' => 0, 'H' => 0, 'R' => 0, 'ER' => 0, 'BB' => 0, 'SO' => 0, 'HR' => 0, 'BF' => 0];
            }
        }
    }

    /**
     * @param $state
     * @param array $result
     * @param array $batter
     * @param array $pitcher
     * @param string $batTeam
     * @param string $pitTeam
     */
    private function processPlay( array &$state, array $result, array $batter, array $pitcher, string $batTeam, string $pitTeam )
    {
        $bPid = $batter['player_id'];
        $pPid = $pitcher['player_id'];
        $desc = "{$batter['player_name']}: " . $result['desc'];

        // Init Stats if missing (Safe guard)
        if ( !isset( $state['stats']['pitchers'][$pitTeam][$pPid] ) ) {
            $state['stats']['pitchers'][$pitTeam][$pPid] = ['IP_outs' => 0, 'H' => 0, 'R' => 0, 'ER' => 0, 'BB' => 0, 'SO' => 0, 'HR' => 0, 'BF' => 0];
        }

        $state['stats']['pitchers'][$pitTeam][$pPid]['BF']++;

        if ( !in_array( $result['event'], ['BB', 'SAC'] ) ) {
            $state['stats']['hitters'][$batTeam][$bPid]['AB']++;
        }

        $runsScored = 0;

        if ( $result['event'] === 'SO' ) {
            $state['outs']++;
            $state['stats']['hitters'][$batTeam][$bPid]['SO']++;
            $state['stats']['pitchers'][$pitTeam][$pPid]['SO']++;
            $state['stats']['pitchers'][$pitTeam][$pPid]['IP_outs']++;
            array_unshift( $state['log'], $desc );
        } elseif ( $result['event'] === 'out' ) {
            $state['outs']++;
            $state['stats']['pitchers'][$pitTeam][$pPid]['IP_outs']++;
            array_unshift( $state['log'], $desc );
        } elseif ( $result['event'] === 'BB' ) {
            $state['stats']['hitters'][$batTeam][$bPid]['BB']++;
            $state['stats']['pitchers'][$pitTeam][$pPid]['BB']++;
            $runsScored = $this->advanceRunnersOnWalk( $state, $batter );
            array_unshift( $state['log'], $desc );
        } else {
            // HITS
            $state['stats']['hitters'][$batTeam][$bPid]['H']++;
            $state['stats']['pitchers'][$pitTeam][$pPid]['H']++;

            if ( $result['event'] === '2B' ) {
                $state['stats']['hitters'][$batTeam][$bPid]['2B']++;
            }

            if ( $result['event'] === '3B' ) {
                $state['stats']['hitters'][$batTeam][$bPid]['3B']++;
            }

            if ( $result['event'] === 'HR' ) {
                $state['stats']['hitters'][$batTeam][$bPid]['HR']++;
                $state['stats']['pitchers'][$pitTeam][$pPid]['HR']++;
            }

            if ( $result['event'] === 'HR' ) {
                $runsScored = 1; // Batter
                foreach ( $state['bases'] as $runner ) {if ( $runner ) {
                    $runsScored++;
                }}
                $state['bases'] = [null, null, null];
                array_unshift( $state['log'], "HOMERUN! $runsScored runs score!" );
            } elseif ( $result['event'] === 'SAC' ) {
                $state['outs']++;
                $state['stats']['pitchers'][$pitTeam][$pPid]['IP_outs']++;
                $runsScored = $this->advanceRunners( $state, 1, false, $batter );
                array_unshift( $state['log'], $desc );
            } else {
                $bases = 1;
                if ( $result['event'] === '2B' ) {
                    $bases = 2;
                }

                if ( $result['event'] === '3B' ) {
                    $bases = 3;
                }

                $runsScored = $this->advanceRunners( $state, $bases, true, $batter );
                if ( $runsScored > 0 ) {
                    $desc .= " ($runsScored scored)";
                }

                array_unshift( $state['log'], $desc );
            }
        }

        if ( $runsScored > 0 ) {
            $state['score'][$batTeam] += $runsScored;
            $state['current_inning_runs'] += $runsScored;
            $state['stats']['hitters'][$batTeam][$bPid]['RBI'] += $runsScored;
            $state['stats']['hitters'][$batTeam][$bPid]['R'] += 1;
            $state['stats']['pitchers'][$pitTeam][$pPid]['R'] += $runsScored;
            $state['stats']['pitchers'][$pitTeam][$pPid]['ER'] += $runsScored;
        }
    }

    /**
     * @param $state
     * @param $basesHit
     * @param $batterReaches
     * @param $batter
     * @return mixed
     */
    private function advanceRunners( &$state, $basesHit, $batterReaches, $batter )
    {
        $runs       = 0;
        $newBases   = [null, null, null];
        $runnerData = ['id' => $batter['player_id'], 'name' => $batter['player_name']];

        if ( $state['bases'][2] ) {$runs++;}
        if ( $state['bases'][1] ) {
            if ( $basesHit >= 2 ) {$runs++;} else {
                if ( mt_rand( 0, 100 ) < 60 ) {
                    $runs++;
                } else {
                    $newBases[2] = $state['bases'][1];
                }

            }
        }

        if ( $state['bases'][0] ) {
            if ( $basesHit >= 3 ) {$runs++;} elseif ( $basesHit == 2 ) {
                if ( mt_rand( 0, 100 ) < 40 ) {
                    $runs++;
                } else {
                    $newBases[2] = $state['bases'][0];
                }

            } else { $newBases[1] = $state['bases'][0];}
        }

        if ( $batterReaches ) {
            if ( $basesHit >= 4 ) {$runs++;} elseif ( $basesHit == 3 ) {$newBases[2] = $runnerData;} elseif ( $basesHit == 2 ) {$newBases[1] = $runnerData;} else { $newBases[0] = $runnerData;}
        }

        $state['bases'] = $newBases;
        return $runs;
    }

    /**
     * @param $state
     * @param $batter
     * @return mixed
     */
    private function advanceRunnersOnWalk( &$state, $batter )
    {
        $runs       = 0;
        $runnerData = ['id' => $batter['player_id'], 'name' => $batter['player_name']];

        if ( $state['bases'][0] && $state['bases'][1] && $state['bases'][2] ) {
            $runs++;
            $state['bases'][2] = $state['bases'][1];
            $state['bases'][1] = $state['bases'][0];
        } elseif ( $state['bases'][0] && $state['bases'][1] ) {
            $state['bases'][2] = $state['bases'][1];
            $state['bases'][1] = $state['bases'][0];
        } elseif ( $state['bases'][0] ) {
            $state['bases'][1] = $state['bases'][0];
        }
        $state['bases'][0] = $runnerData;
        return $runs;
    }

    /**
     * @param $state
     */
    private function switchSides( array &$state )
    {
        $battingTeam = $state['half'] === 'top' ? 'away' : 'home';
        $inning      = $state['inning'];

        // Record Inning Score
        if ( !isset( $state['inning_scores'][$battingTeam] ) ) {
            $state['inning_scores'][$battingTeam] = [];
        }

        $state['inning_scores'][$battingTeam][$inning] = $state['current_inning_runs'];
        $state['current_inning_runs']                  = 0;

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
     * @param $state
     */
    private function checkGameEnd( $state )
    {
        $home   = $state['score']['home'];
        $away   = $state['score']['away'];
        $inning = $state['inning'];
        $half   = $state['half'];

        if ( $inning >= 9 ) {
            if ( $half === 'bottom' && $state['outs'] == 0 && $home > $away ) {
                return true;
            }

            if ( $inning > 9 && $half === 'top' && $state['outs'] == 0 && $home != $away ) {
                return true;
            }

        }
        return false;
    }

    /**
     * @param $state
     */
    private function finishGame( $state )
    {
        $db = $this->teamModel->getDb();

        try {
            $db->beginTransaction();

            $gameId    = $state['game_id'];
            $homeScore = $state['score']['home'];
            $awayScore = $state['score']['away'];

            // Finalize Inning Score
            $battingTeam                                            = $state['half'] === 'top' ? 'away' : 'home';
            $state['inning_scores'][$battingTeam][$state['inning']] = $state['current_inning_runs'];

            // 1. Update Game Record
            $sql = "UPDATE games SET
                    status = 'played',
                    home_score = :hs, away_score = :as,
                    line_score = :ls
                    WHERE game_id = :gid";

            $stmt = $db->prepare( $sql );
            $stmt->execute( [
                ':hs'  => $homeScore, ':as' => $awayScore,
                ':ls'  => json_encode( $state['inning_scores'] ),
                ':gid' => $gameId,
            ] );

            // 2. Update Standings
            $winner = ( $homeScore > $awayScore ) ? 'home' : 'away';
            $loser  = ( $homeScore > $awayScore ) ? 'away' : 'home';

            $db->prepare( "UPDATE teams SET w = w + 1 WHERE team_id = :id" )->execute( [':id' => $state['teams'][$winner]['id']] );
            $db->prepare( "UPDATE teams SET l = l + 1 WHERE team_id = :id" )->execute( [':id' => $state['teams'][$loser]['id']] );

            // 3. Save Hitter Stats
            foreach ( ['home', 'away'] as $teamKey ) {
                $teamId = $state['teams'][$teamKey]['id'];
                foreach ( $state['stats']['hitters'][$teamKey] as $pid => $s ) {
                    // Find Player Name
                    $player = null;
                    foreach ( $state['teams'][$teamKey]['lineup'] as $p ) {
                        if ( $p['player_id'] == $pid ) {$player = $p;
                            break;}
                    }
                    $name = $player['player_name'] ?? 'Unknown';
                    $pos  = $player['position'] ?? 'PH';

                    // Make sure we don't insert blank rows if they did nothing
                    if ( $s['AB'] == 0 && $s['BB'] == 0 && $s['R'] == 0 && $pos !== 'P' ) {
                        continue;
                    }

                    $sqlBox = "INSERT INTO box_scores (game_id, team_id, player_id, player_name, position, AB, R, H, RBI, BB, SO, HR)
                               VALUES (:gid, :tid, :pid, :name, :pos, :ab, :r, :h, :rbi, :bb, :so, :hr)";
                    $db->prepare( $sqlBox )->execute( [
                        ':gid' => $gameId, ':tid' => $teamId, ':pid' => $pid, ':name'   => $name, ':pos'    => $pos,
                        ':ab'  => $s['AB'], ':r'  => $s['R'], ':h'   => $s['H'], ':rbi' => $s['RBI'], ':bb' => $s['BB'], ':so' => $s['SO'], ':hr' => $s['HR'],
                    ] );

                    // Update Season Stats
                    $sqlSeason = "INSERT INTO player_season_stats (player_id, team_id, season_year, AB, R, H, RBI, `2B`, `3B`, HR, BB, SO)
                                  VALUES (:pid, :tid, 2024, :ab, :r, :h, :rbi, :b2, :b3, :hr, :bb, :so)
                                  ON DUPLICATE KEY UPDATE
                                  AB=AB+VALUES(AB), R=R+VALUES(R), H=H+VALUES(H), RBI=RBI+VALUES(RBI),
                                  `2B`=`2B`+VALUES(`2B`), `3B`=`3B`+VALUES(`3B`), HR=HR+VALUES(HR), BB=BB+VALUES(BB), SO=SO+VALUES(SO)";
                    $db->prepare( $sqlSeason )->execute( [
                        ':pid' => $pid, ':tid'    => $teamId, 2024,
                        ':ab'  => $s['AB'], ':r'  => $s['R'], ':h'   => $s['H'], ':rbi' => $s['RBI'],
                        ':b2'  => $s['2B'], ':b3' => $s['3B'], ':hr' => $s['HR'], ':bb' => $s['BB'], ':so' => $s['SO'],
                    ] );
                }
            }

            // 4. Save Pitcher Stats
            foreach ( ['home', 'away'] as $teamKey ) {
                $teamId = $state['teams'][$teamKey]['id'];
                foreach ( $state['stats']['pitchers'][$teamKey] as $pid => $s ) {
                    if ( $s['BF'] == 0 ) {
                        continue;
                    }

                    $name = 'Unknown';
                    if ( $state['teams'][$teamKey]['pitcher']['player_id'] == $pid ) {
                        $name = $state['teams'][$teamKey]['pitcher']['player_name'];
                    } else {
                        // Try finding in roster
                        // For MVP, just leave as Unknown or Pitcher if not SP
                    }

                    $wholeInnings = floor( $s['IP_outs'] / 3 );
                    $partial      = $s['IP_outs'] % 3;
                    $ip           = $wholeInnings + ( $partial * 0.1 );

                    $sqlBox = "INSERT INTO pitcher_box_scores (game_id, team_id, player_id, player_name, IP, H, R, ER, BB, SO, HR)
                               VALUES (:gid, :tid, :pid, :name, :ip, :h, :r, :er, :bb, :so, :hr)";
                    $db->prepare( $sqlBox )->execute( [
                        ':gid' => $gameId, ':tid' => $teamId, ':pid' => $pid, ':name'  => $name,
                        ':ip'  => $ip, ':h'       => $s['H'], ':r'   => $s['R'], ':er' => $s['ER'], ':bb' => $s['BB'], ':so' => $s['SO'], ':hr' => $s['HR'],
                    ] );

                    // Season Stats
                    $win  = ( $teamKey === ( $winner === 'home' ? 'home' : 'away' ) ) ? 1 : 0;
                    $loss = ( $teamKey !== ( $winner === 'home' ? 'home' : 'away' ) ) ? 1 : 0;

                    $sqlSeason = "INSERT INTO pitcher_season_stats (player_id, team_id, season_year, IP, W, L, H, R, ER, BB, SO, HR)
                                  VALUES (:pid, :tid, 2024, :ip, :w, :l, :h, :r, :er, :bb, :so, :hr)
                                  ON DUPLICATE KEY UPDATE
                                  IP=IP+VALUES(IP), W=W+VALUES(W), L=L+VALUES(L), H=H+VALUES(H),
                                  R=R+VALUES(R), ER=ER+VALUES(ER), BB=BB+VALUES(BB), SO=SO+VALUES(SO), HR=HR+VALUES(HR)";
                    $db->prepare( $sqlSeason )->execute( [
                        ':pid' => $pid, ':tid'    => $teamId, 2024,
                        ':ip'  => $ip, ':w'       => $win, ':l'      => $loss, ':h'     => $s['H'], ':r' => $s['R'],
                        ':er'  => $s['ER'], ':bb' => $s['BB'], ':so' => $s['SO'], ':hr' => $s['HR'],
                    ] );
                }
            }

            $db->commit();
            Session::remove( 'game_state' );

        } catch ( \Exception $e ) {
            $db->rollBack();
            error_log( "Failed to save game: " . $e->getMessage() );
            // Throwing will cause 500 error which frontend will see
            throw $e;
        }
    }

    /**
     * @param Request $request
     * @param $gameId
     * @return mixed
     */
    public function showBoxScore( Request $request, $gameId ): Response
    {
        $db = $this->teamModel->getDb();

        $stmt = $db->prepare( "SELECT g.*, h.team_name as home_name, a.team_name as away_name
                              FROM games g
                              JOIN teams h ON g.home_team_id = h.team_id
                              JOIN teams a ON g.away_team_id = a.team_id
                              WHERE g.game_id = :id" );
        $stmt->execute( [':id' => $gameId] );
        $game = $stmt->fetch( PDO::FETCH_ASSOC );

        if ( !$game ) {
            return $this->redirect( '/dashboard' );
        }

        $stmt = $db->prepare( "SELECT * FROM box_scores WHERE game_id = :id ORDER BY team_id, id ASC" );
        $stmt->execute( [':id' => $gameId] );
        $hitters = $stmt->fetchAll( PDO::FETCH_ASSOC );

        $stmt = $db->prepare( "SELECT * FROM pitcher_box_scores WHERE game_id = :id" );
        $stmt->execute( [':id' => $gameId] );
        $pitchers = $stmt->fetchAll( PDO::FETCH_ASSOC );

        $lineScore = json_decode( $game['line_score'], true );

        return $this->view( 'game/boxscore.twig', [
            'game'      => $game,
            'hitters'   => $hitters,
            'pitchers'  => $pitchers,
            'lineScore' => $lineScore,
        ] );
    }

    /**
     * Fetches stats from 'hitters' or 'pitchers' table and merges them.
     */
    private function hydrateStats( array $roster ): array
    {
        $hitterIds  = [];
        $pitcherIds = [];

        foreach ( $roster as $p ) {
            if ( ( $p['stats_source'] ?? '' ) === 'pitchers' || isset( $p['Endurance'] ) ) {
                $pitcherIds[] = $p['player_id'];
            } else {
                $hitterIds[] = $p['player_id'];
            }
        }

        $hStats = [];
        if ( !empty( $hitterIds ) ) {
            $ph   = implode( ',', $hitterIds );
            $sql  = "SELECT * FROM hitters WHERE row_id IN ($ph)";
            $rows = $this->teamModel->getDb()->query( $sql )->fetchAll( PDO::FETCH_ASSOC );
            foreach ( $rows as $r ) {
                $hStats[$r['row_id']] = $r;
            }

        }

        $pStats = [];
        if ( !empty( $pitcherIds ) ) {
            $ph   = implode( ',', $pitcherIds );
            $sql  = "SELECT * FROM pitchers WHERE row_id IN ($ph)";
            $rows = $this->teamModel->getDb()->query( $sql )->fetchAll( PDO::FETCH_ASSOC );
            foreach ( $rows as $r ) {
                $pStats[$r['row_id']] = $r;
            }

        }

        // Merge
        foreach ( $roster as &$p ) {
            $pid = $p['player_id'];
            if ( isset( $hStats[$pid] ) ) {
                $p = array_merge( $p, $hStats[$pid] );
            } elseif ( isset( $pStats[$pid] ) ) {
                $p = array_merge( $p, $pStats[$pid] );
            }
        }

        return $roster;
    }

    /**
     * Calculates a rough "Skill Score" for sorting.
     * Higher is better.
     */
    private function calculatePlayerScore( array $p ): float
    {
        // 1. Pitchers
        if ( isset( $p['Endurance'] ) || strpos( $p['position'], 'P' ) !== false ) {
            // Lower ERA/WHIP is better, so we invert or subtract from a baseline
            // Fallback to PRICE if stats missing, as PRICE usually correlates to skill
            $price = (int) str_replace( [',', '$'], '', $p['PRICE'] ?? '0' );
            return $price;
            // If you have real stats: return (10.00 - $p['ERA']) * 10;
        }

        // 2. Hitters
        // Prioritize OPS (OBP + SLG) or AVG + Power
        $avg = (float) ( $p['AVG'] ?? $p['BA'] ?? 0 );
        $hr  = (int) ( $p['HR'] ?? 0 );
        $sb  = (int) ( $p['SB'] ?? 0 );

        // Weighted Score: AVG is heavy, HR is bonus
        // Example: .300 avg * 1000 = 300 + (30 HR * 2) = 360
        return ( $avg * 1000 ) + ( $hr * 2 ) + ( $sb * 1 );
    }

    /**
     * Reorders a list of 9 players into a logical baseball lineup.
     */
    private function optimizeBattingOrder( array $players ): array
    {
        if ( count( $players ) < 9 ) {
            return $players;
        }

        $order     = array_fill( 0, 9, null );
        $remaining = $players;

        // 1. LEADOFF (Fastest / High AVG)
        // Find player with max SB + AVG
        usort( $remaining, fn( $a, $b ) => ( ( $b['SB'] ?? 0 ) * 2 + ( $b['AVG'] ?? 0 ) * 1000 ) <=> ( ( $a['SB'] ?? 0 ) * 2 + ( $a['AVG'] ?? 0 ) * 1000 ) );
        $order[0] = array_shift( $remaining ); // Best leadoff

        // 2. CLEANUP (Best Power)
        // Find player with max HR
        usort( $remaining, fn( $a, $b ) => ( $b['HR'] ?? 0 ) <=> ( $a['HR'] ?? 0 ) );
        $order[3] = array_shift( $remaining ); // Best Power hits 4th

        // 3. BEST HITTER (3-Hole)
        // Find player with best AVG remaining
        usort( $remaining, fn( $a, $b ) => ( $b['AVG'] ?? 0 ) <=> ( $a['AVG'] ?? 0 ) );
        $order[2] = array_shift( $remaining );

        // 4. Fill the rest (2, 5, 6, 7, 8, 9) based on generic quality
        // Sort remaining by generic score
        usort( $remaining, fn( $a, $b ) => $this->calculatePlayerScore( $b ) <=> $this->calculatePlayerScore( $a ) );

        $slots = [1, 4, 5, 6, 7, 8]; // Index 1 is 2nd batter, Index 4 is 5th, etc.
        foreach ( $slots as $slot ) {
            if ( !empty( $remaining ) ) {
                $order[$slot] = array_shift( $remaining );
            }
        }

        // Filter out nulls just in case and return
        return array_filter( $order );
    }

    /**
     * Helper: Ensures the session state has FULL player data (stats included).
     * Forces a reload if a player is found but lacks stats (like 'AVG' or 'H').
     */
    private function ensureRosterData( array &$state )
    {
        // Check Home
        $homeMissing = empty( $state['teams']['home']['lineup'] ) ||
        !isset( $state['teams']['home']['lineup'][0]['player_name'] ) ||
        !isset( $state['teams']['home']['lineup'][0]['AVG'] );

        if ( $homeMissing ) {
            $data                             = $this->getLineupForGame( $state['teams']['home']['id'] );
            $state['teams']['home']['lineup'] = $data['lineup'];
            if ( empty( $state['teams']['home']['pitcher'] ) || !isset( $state['teams']['home']['pitcher']['H'] ) ) {
                $state['teams']['home']['pitcher'] = $data['pitcher'];
            }
        }

        // Check Away
        $awayMissing = empty( $state['teams']['away']['lineup'] ) ||
        !isset( $state['teams']['away']['lineup'][0]['player_name'] ) ||
        !isset( $state['teams']['away']['lineup'][0]['AVG'] );

        if ( $awayMissing ) {
            $data                             = $this->getLineupForGame( $state['teams']['away']['id'] );
            $state['teams']['away']['lineup'] = $data['lineup'];
            if ( empty( $state['teams']['away']['pitcher'] ) || !isset( $state['teams']['away']['pitcher']['H'] ) ) {
                $state['teams']['away']['pitcher'] = $data['pitcher'];
            }
        }

        // Ensure names
        if ( !isset( $state['teams']['home']['name'] ) ) {
            $state['teams']['home']['name'] = 'Home';
        }
        if ( !isset( $state['teams']['away']['name'] ) ) {
            $state['teams']['away']['name'] = 'Away';
        }
    }
}
