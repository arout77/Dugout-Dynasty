<?php

namespace App\Services;

use App\Models\Roster;
use App\Models\Team;
use App\Models\TeamStrategy;
use PDO;

class QuickSimService
{
    protected SimulationService $simService;
    protected Team $teamModel;
    protected Roster $rosterModel;
    protected TeamStrategy $strategyModel;
    /**
     * @var mixed
     */
    protected $db;

    public function __construct()
    {
        $this->simService    = new SimulationService();
        $this->teamModel     = new Team();
        $this->rosterModel   = new Roster();
        $this->strategyModel = new TeamStrategy();
        $this->db            = $this->teamModel->getDb();
    }

    /**
     * Simulates all unplayed games scheduled for a specific date.
     * FIX: Strict equality on date to prevent runaway simulations of the whole season.
     */
    public function simulateDay( string $date )
    {
        // FIX: Only play games FOR this specific date, not all past unplayed games.
        // This prevents simulating the entire backlog if dates are messed up.
        $sql  = "SELECT game_id FROM games WHERE game_date = :date AND status = 'scheduled'";
        $stmt = $this->db->prepare( $sql );
        $stmt->execute( [':date' => $date] );
        $games = $stmt->fetchAll( PDO::FETCH_COLUMN );

        // Safety Limit: Never sim more than 15 games (one full league day) in one batch
        // to prevent timeouts if the schedule is broken.
        $games = array_slice( $games, 0, 15 );

        foreach ( $games as $gameId ) {
            $this->simulateGame( (int) $gameId );
        }

        return count( $games );
    }

    /**
     * Runs a single game simulation start-to-finish.
     */
    public function simulateGame( int $gameId )
    {
        // 1. Fetch Game Info
        $stmt = $this->db->prepare( "SELECT * FROM games WHERE game_id = :id" );
        $stmt->execute( [':id' => $gameId] );
        $game = $stmt->fetch( PDO::FETCH_ASSOC );

        if ( !$game || $game['status'] === 'played' ) {
            return;
        }

        // 2. Setup Lineups
        $homeLineup = $this->getLineup( $game['home_team_id'] );
        $awayLineup = $this->getLineup( $game['away_team_id'] );

        // 3. Init Stats & State
        $state = $this->initializeState( $game, $homeLineup, $awayLineup );

        // 4. Play Ball (The Loop)
        $gameOver = false;
        while ( !$gameOver ) {
            $this->playAtBat( $state );

            // Check End Conditions
            if ( $state['inning'] >= 9 ) {
                // Bottom of 9th+ and Home Winning?
                if ( $state['half'] === 'bottom' && $state['score']['home'] > $state['score']['away'] ) {
                    $gameOver = true;
                }
                // End of Inning and scores not tied?
                if ( $state['outs'] === 3 && $state['half'] === 'bottom' && $state['score']['home'] != $state['score']['away'] ) {
                    $gameOver = true;
                }
            }

            // Mercy / Fatigue Fail-safe (Limit to 20 innings)
            if ( $state['inning'] > 20 ) {
                $gameOver = true;
            }

        }

        // 5. Save Results
        $this->saveGame( $state );
    }

    /**
     * @param $state
     */
    private function playAtBat( &$state )
    {
        $batTeam = $state['half'] === 'top' ? 'away' : 'home';
        $pitTeam = $state['half'] === 'top' ? 'home' : 'away';

        $batterIdx = $state['current_batter_index'][$batTeam];
        $batter    = $state['teams'][$batTeam]['lineup'][$batterIdx];
        $pitcher   = $state['teams'][$pitTeam]['pitcher'];

        // Sim
        $result = $this->simService->simulateAtBat( $batter, $pitcher );

        // Update State (Simplified ProcessPlay)
        $this->processResult( $state, $result, $batter, $pitcher, $batTeam, $pitTeam );

        // Next Batter
        $state['current_batter_index'][$batTeam] = ( $batterIdx + 1 ) % 9;

        // Outs Logic
        if ( $state['outs'] >= 3 ) {
            $this->switchSides( $state );
        }
    }

    /**
     * @param $state
     * @param $result
     * @param $batter
     * @param $pitcher
     * @param $batTeam
     * @param $pitTeam
     */
    private function processResult( &$state, $result, $batter, $pitcher, $batTeam, $pitTeam )
    {
        $bPid = $batter['player_id'];
        $pPid = $pitcher['player_id'];

        // Track Batters Faced
        $state['stats']['pitchers'][$pitTeam][$pPid]['BF']++;

        if ( !in_array( $result['event'], ['BB', 'SAC'] ) ) {
            $state['stats']['hitters'][$batTeam][$bPid]['AB']++;
        }

        $runs = 0;

        if ( $result['event'] === 'SO' ) {
            $state['outs']++;
            $state['stats']['hitters'][$batTeam][$bPid]['SO']++;
            $state['stats']['pitchers'][$pitTeam][$pPid]['SO']++;
            $state['stats']['pitchers'][$pitTeam][$pPid]['IP_outs']++;
        } elseif ( $result['event'] === 'out' ) {
            $state['outs']++;
            $state['stats']['pitchers'][$pitTeam][$pPid]['IP_outs']++;
        } elseif ( $result['event'] === 'BB' ) {
            $state['stats']['hitters'][$batTeam][$bPid]['BB']++;
            $state['stats']['pitchers'][$pitTeam][$pPid]['BB']++;
            $runs = $this->advanceRunners( $state, 1, true, true ); // Force logic for walk
        } else {
            // Hits
            $state['stats']['hitters'][$batTeam][$bPid]['H']++;
            $state['stats']['pitchers'][$pitTeam][$pPid]['H']++;

            $bases = 1;
            if ( $result['event'] === '2B' ) {
                $bases = 2;
                $state['stats']['hitters'][$batTeam][$bPid]['2B']++;
            }
            if ( $result['event'] === '3B' ) {
                $bases = 3;
                $state['stats']['hitters'][$batTeam][$bPid]['3B']++;
            }
            if ( $result['event'] === 'HR' ) {
                $bases = 4;
                $state['stats']['hitters'][$batTeam][$bPid]['HR']++;
                $state['stats']['pitchers'][$pitTeam][$pPid]['HR']++;
            }

            $runs = $this->advanceRunners( $state, $bases, true, false );
        }

        if ( $runs > 0 ) {
            $state['score'][$batTeam] += $runs;
            $state['current_inning_runs'] += $runs;
            $state['stats']['hitters'][$batTeam][$bPid]['RBI'] += $runs;
            $state['stats']['hitters'][$batTeam][$bPid]['R']++;
            $state['stats']['pitchers'][$pitTeam][$pPid]['R'] += $runs;
            $state['stats']['pitchers'][$pitTeam][$pPid]['ER'] += $runs;
        }
    }

    // Simplified Runner Logic for Speed
    /**
     * @param $state
     * @param $basesHit
     * @param $isHit
     * @param $isWalk
     * @return mixed
     */
    private function advanceRunners( &$state, $basesHit, $isHit, $isWalk )
    {
        $runs     = 0;
        $newBases = [null, null, null];

        if ( $isWalk ) {
            // Bases loaded? Run. Else force.
            if ( $state['bases'][0] && $state['bases'][1] && $state['bases'][2] ) {
                $runs++;
            } elseif ( $state['bases'][0] && $state['bases'][1] ) {$state['bases'][2] = true;} elseif ( $state['bases'][0] ) {$state['bases'][1] = true;}
            $state['bases'][0] = true;
            return $runs;
        }

        // HR
        if ( $basesHit == 4 ) {
            $runs = 1; // Batter
            foreach ( $state['bases'] as $r ) {
                if ( $r ) {
                    $runs++;
                }
            }

            $state['bases'] = [null, null, null];
            return $runs;
        }

        // Standard Hits
        // Runner on 3rd
        if ( $state['bases'][2] ) {
            $runs++;
        }

        // Runner on 2nd
        if ( $state['bases'][1] ) {
            if ( $basesHit >= 2 ) {
                $runs++;
            } else {
                // Single scores from 2nd 50% of time in sim
                if ( rand( 0, 1 ) ) {
                    $runs++;
                } else {
                    $newBases[2] = true;
                }

            }
        }

        // Runner on 1st
        if ( $state['bases'][0] ) {
            if ( $basesHit >= 3 ) {
                $runs++;
            } elseif ( $basesHit == 2 ) {
                // Double scores from 1st 40% of time
                if ( rand( 0, 100 ) < 40 ) {
                    $runs++;
                } else {
                    $newBases[2] = true;
                }

            } else {
                $newBases[1] = true;
            }

        }

        // Batter
        if ( $basesHit == 3 ) {
            $newBases[2] = true;
        } elseif ( $basesHit == 2 ) {
            $newBases[1] = true;
        } else {
            $newBases[0] = true;
        }

        $state['bases'] = $newBases;
        return $runs;
    }

    /**
     * @param $state
     */
    private function switchSides( &$state )
    {
        $batTeam                                            = $state['half'] === 'top' ? 'away' : 'home';
        $state['inning_scores'][$batTeam][$state['inning']] = $state['current_inning_runs'];
        $state['current_inning_runs']                       = 0;
        $state['outs']                                      = 0;
        $state['bases']                                     = [null, null, null];

        if ( $state['half'] === 'top' ) {
            $state['half'] = 'bottom';
        } else {
            $state['half'] = 'top';
            $state['inning']++;
        }
    }

    /**
     * @param $state
     */
    private function saveGame( $state )
    {
        // 1. Update Game
        $sql = "UPDATE games SET status='played', home_score=:hs, away_score=:as, line_score=:ls WHERE game_id=:gid";
        $this->db->prepare( $sql )->execute( [
            ':hs' => $state['score']['home'], ':as'               => $state['score']['away'],
            ':ls' => json_encode( $state['inning_scores'] ), ':gid' => $state['game_id'],
        ] );

        // 2. Standings
        $winner = ( $state['score']['home'] > $state['score']['away'] ) ? 'home' : 'away';
        $loser  = ( $winner === 'home' ) ? 'away' : 'home';
        $this->db->prepare( "UPDATE teams SET w = w + 1 WHERE team_id = :id" )->execute( [':id' => $state['teams'][$winner]['id']] );
        $this->db->prepare( "UPDATE teams SET l = l + 1 WHERE team_id = :id" )->execute( [':id' => $state['teams'][$loser]['id']] );

        // 3. Hitters & Pitchers Stats
        // (Simplified bulk save logic - reuse your GameController logic here for robustness)
        // For brevity, I will invoke the same loops as GameController:
        foreach ( ['home', 'away'] as $side ) {
            $tid = $state['teams'][$side]['id'];
            foreach ( $state['stats']['hitters'][$side] as $pid => $s ) {
                if ( $s['AB'] + $s['BB'] == 0 ) {
                    continue;
                }

                // Name lookup
                $name = 'Unknown';
                foreach ( $state['teams'][$side]['lineup'] as $p ) {if ( $p['player_id'] == $pid ) {
                    $name = $p['player_name'];
                }}

                $sql = "INSERT INTO player_season_stats (player_id, team_id, season_year, AB, R, H, RBI, `2B`, `3B`, HR, BB, SO)
                        VALUES (:pid, :tid, 2024, :ab, :r, :h, :rbi, :b2, :b3, :hr, :bb, :so)
                        ON DUPLICATE KEY UPDATE AB=AB+VALUES(AB), R=R+VALUES(R), H=H+VALUES(H), RBI=RBI+VALUES(RBI), `2B`=`2B`+VALUES(`2B`), `3B`=`3B`+VALUES(`3B`), HR=HR+VALUES(HR), BB=BB+VALUES(BB), SO=SO+VALUES(SO)";
                $this->db->prepare( $sql )->execute( [
                    ':pid' => $pid, ':tid'    => $tid, ':ab'     => $s['AB'], ':r'  => $s['R'], ':h'   => $s['H'], ':rbi' => $s['RBI'],
                    ':b2'  => $s['2B'], ':b3' => $s['3B'], ':hr' => $s['HR'], ':bb' => $s['BB'], ':so' => $s['SO'],
                ] );
            }

            // Pitchers
            foreach ( $state['stats']['pitchers'][$side] as $pid => $s ) {
                if ( $s['BF'] == 0 ) {
                    continue;
                }

                $ip = floor( $s['IP_outs'] / 3 ) + ( ( $s['IP_outs'] % 3 ) * 0.1 );
                $w  = ( $side === $winner ) ? 1 : 0;
                $l  = ( $side === $loser ) ? 1 : 0;

                $sql = "INSERT INTO pitcher_season_stats (player_id, team_id, season_year, IP, W, L, H, R, ER, BB, SO, HR)
                        VALUES (:pid, :tid, 2024, :ip, :w, :l, :h, :r, :er, :bb, :so, :hr)
                        ON DUPLICATE KEY UPDATE IP=IP+VALUES(IP), W=W+VALUES(W), L=L+VALUES(L), H=H+VALUES(H), R=R+VALUES(R), ER=ER+VALUES(ER), BB=BB+VALUES(BB), SO=SO+VALUES(SO), HR=HR+VALUES(HR)";
                $this->db->prepare( $sql )->execute( [
                    ':pid' => $pid, ':tid'  => $tid, ':ip'    => $ip, ':w'       => $w, ':l'        => $l,
                    ':h'   => $s['H'], ':r' => $s['R'], ':er' => $s['ER'], ':bb' => $s['BB'], ':so' => $s['SO'], ':hr' => $s['HR'],
                ] );
            }
        }
    }

    /**
     * @param $teamId
     */
    private function getLineup( $teamId )
    {
        $roster = $this->rosterModel->getPlayersByTeamWithStats( $teamId );

        $lineup  = [];
        $pitcher = null;

        // FIX: Check Endurance for 'S' (Starting Pitcher)
        // Logic: S* (Start only or Start/Relief) is valid. R* (Relief only) is NOT valid.
        foreach ( $roster as $p ) {
            $endurance = $p['Endurance'] ?? $p['ENDURANCE'] ?? '';
            // Check if it starts with 'S'. Example: "S7" or "S6/R3"
            if ( !empty( $endurance ) && strtoupper( $endurance[0] ) === 'S' ) {
                $pitcher = $p;
                break;
            }
        }

        // Ghost Pitcher if totally empty
        if ( !$pitcher ) {
            $pitcher = [
                'player_id'   => 0,
                'player_name' => 'Ghost Pitcher',
                'position'    => 'P',
                'H'           => 180, 'BB' => 50, 'SO' => 150, 'IP' => 200, 'BF' => 800, 'HR' => 20,
            ];
        }

        // Fill Batters
        foreach ( $roster as $p ) {
            if ( ( $p['player_id'] ?? 0 ) != ( $pitcher['player_id'] ?? 0 ) && strpos( $p['position'] ?? '', 'P' ) === false ) {
                $lineup[] = $p;
            }
            if ( count( $lineup ) == 9 ) {
                break;
            }

        }

        while ( count( $lineup ) < 9 ) {
            $lineup[] = ['player_id' => -1, 'player_name' => 'Ghost Hitter', 'AVG' => .250, 'HR' => 10];
        }

        return ['lineup' => $lineup, 'pitcher' => $pitcher];
    }

    /**
     * @param $game
     * @param $home
     * @param $away
     */
    private function initializeState( $game, $home, $away )
    {
        $initH = fn() => ['AB' => 0, 'R' => 0, 'H' => 0, 'RBI' => 0, '2B' => 0, '3B' => 0, 'HR' => 0, 'BB' => 0, 'SO' => 0];
        $initP = fn() => ['IP_outs' => 0, 'H' => 0, 'R' => 0, 'ER' => 0, 'BB' => 0, 'SO' => 0, 'HR' => 0, 'BF' => 0];

        $stats = ['hitters' => ['home' => [], 'away' => []], 'pitchers' => ['home' => [], 'away' => []]];
        foreach ( $home['lineup'] as $p ) {
            $stats['hitters']['home'][$p['player_id']] = $initH();
        }

        foreach ( $away['lineup'] as $p ) {
            $stats['hitters']['away'][$p['player_id']] = $initH();
        }

        $stats['pitchers']['home'][$home['pitcher']['player_id']] = $initP();
        $stats['pitchers']['away'][$away['pitcher']['player_id']] = $initP();

        return [
            'game_id'              => $game['game_id'],
            'inning'               => 1, 'half' => 'top', 'outs' => 0,
            'score'                => ['home' => 0, 'away' => 0],
            'bases'                => [null, null, null],
            'teams'                => ['home' => ['id' => $game['home_team_id'], 'lineup' => $home['lineup'], 'pitcher' => $home['pitcher']], 'away' => ['id' => $game['away_team_id'], 'lineup' => $away['lineup'], 'pitcher' => $away['pitcher']]],
            'current_batter_index' => ['home' => 0, 'away' => 0],
            'stats'                => $stats,
            'inning_scores'        => ['home' => [], 'away' => []],
            'current_inning_runs'  => 0,
        ];
    }
}
