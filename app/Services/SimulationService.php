<?php

namespace App\Services;

use App\Services\LeagueStatsService;

class SimulationService
{
    protected LeagueStatsService $leagueStats;

    // Standard outcomes
    const RESULT_OUT = 'out';
    const RESULT_1B  = '1B';
    const RESULT_2B  = '2B';
    const RESULT_3B  = '3B';
    const RESULT_HR  = 'HR';
    const RESULT_BB  = 'BB';
    const RESULT_SO  = 'SO';

    public function __construct()
    {
        $this->leagueStats = new LeagueStatsService();
    }

    /**
     * @param array $batter
     * @param array $pitcher
     * @return mixed
     */
    public function simulateAtBat( array $batter, array $pitcher ): array
    {
        $bYear = (int) ( $batter['YR'] ?? $batter['Year'] ?? 2024 );
        $pYear = (int) ( $pitcher['YR'] ?? $pitcher['Year'] ?? 2024 );

        $bLeague = $this->leagueStats->getStatsForYear( $bYear );
        $pLeague = $this->leagueStats->getStatsForYear( $pYear );

        $env = $pLeague;

        // 1. WALKS (BB)
        $probBB = $this->calcLog5(
            $this->getRate( $batter, 'BB', 'PA' ),
            $this->getRate( $pitcher, 'BB', 'BF' ),
            $env['avg_bb_pa'] ?? 0.08
        );

        // 2. STRIKEOUTS (SO)
        $probSO = $this->calcLog5(
            $this->getRate( $batter, 'SO', 'PA' ),
            $this->getRate( $pitcher, 'SO', 'BF' ),
            $env['avg_so_pa'] ?? 0.20
        );

        // 3. HOME RUNS (HR)
        $probHR = $this->calcLog5(
            $this->getRate( $batter, 'HR', 'PA' ),
            $this->getRate( $pitcher, 'HR', 'BF' ),
            $env['avg_hr_pa'] ?? 0.03
        );

        // 4. HITS (AVG) - Use Batting Average
        $bAVG = $this->getStat( $batter, 'BA' ) ?? $this->getStat( $batter, 'AVG' ) ?? .250;
        if ( $bAVG < 0.05 ) {
            $bAVG = 0.150;
        }
        // Floor for pitchers hitting

        $probHit = $this->calcLog5(
            $bAVG,
            $this->deriveOpponentAVG( $pitcher ),
            $env['avg_ba'] ?? .250
        );

        // Normalize Outcomes
        $roll = mt_rand( 0, 1000 ) / 1000;

        if ( $roll < $probBB ) {
            return ['event' => self::RESULT_BB, 'desc' => 'Walk'];
        }

        $roll -= $probBB;

        if ( $roll < $probSO ) {
            return ['event' => self::RESULT_SO, 'desc' => 'Strikeout'];
        }

        $roll -= $probSO;

        if ( $roll < $probHR ) {
            return ['event' => self::RESULT_HR, 'desc' => 'Home Run'];
        }

        $roll -= $probHR;

        // Hit Check
        if ( $roll < ( $probHit - $probHR ) ) {
            return $this->determineHitType( $batter );
        }

        return ['event' => self::RESULT_OUT, 'desc' => 'Out'];
    }

    /**
     * @param $b
     * @param $p
     * @param $l
     * @return mixed
     */
    private function calcLog5( $b, $p, $l )
    {
        if ( $l <= 0 ) {
            $l = 0.01;
        }

        if ( $l >= 1 ) {
            $l = 0.99;
        }

        $b       = max( 0.001, min( 0.999, $b ) );
        $p       = max( 0.001, min( 0.999, $p ) );
        $odds    = ( $b * $p ) / $l;
        $inverse = ( ( 1 - $b ) * ( 1 - $p ) ) / ( 1 - $l );
        return $odds / ( $odds + $inverse );
    }

    /**
     * @param $player
     * @param $stat
     * @param $denominator
     * @return mixed
     */
    private function getRate( $player, $stat, $denominator )
    {
        $val = $this->getStat( $player, $stat );
        if ( $stat === 'SO' && $val === 0 ) {
            $val = $this->getStat( $player, 'K' );
        }

        $den = 1;
        if ( $denominator === 'PA' ) {
            $ab  = $this->getStat( $player, 'AB' );
            $bb  = $this->getStat( $player, 'BB' );
            $den = ( $ab > 0 ? $ab : 1 ) + $bb;
        } elseif ( $denominator === 'BF' ) {
            $ip  = $this->getStat( $player, 'IP' );
            $h   = $this->getStat( $player, 'H' );
            $bb  = $this->getStat( $player, 'BB' );
            $den = ( $ip > 0 ? $ip * 2.9 : 1 ) + $h + $bb;

            // Fix: If BF is effectively 0 or missing, return league average rate
            if ( $den < 5 ) {
                // Return defaults based on stat type
                if ( $stat === 'BB' ) {
                    return 0.08;
                }

                if ( $stat === 'SO' ) {
                    return 0.20;
                }

                if ( $stat === 'HR' ) {
                    return 0.03;
                }

            }
        }

        if ( $den == 0 ) {
            return 0;
        }

        return $val / $den;
    }

    /**
     * FIX: Added fallback to .250 if stats are missing
     */
    private function deriveOpponentAVG( $pitcher )
    {
        $h  = $pitcher['H'] ?? 0;
        $bb = $pitcher['BB'] ?? 0;
        $ip = $pitcher['IP'] ?? 0;

        // Baseline
        if ( $h == 0 || $ip < 5 ) {
            return 0.250;
        }

        $bf = ( $ip * 2.9 ) + $h + $bb;
        $ab = $bf - $bb;

        if ( $ab <= 0 ) {
            return .250;
        }

        $baseAvg = $h / $ab;

        // --- FATIGUE LOGIC with SAFETY CAP ---
        // If pitcher has faced too many batters in THIS game, degrade performance.
        // But we must cap it so they can still get an out.
        // Assuming $pitcher['BF_current_game'] is tracked (if not, we use a simpler proxy or skip)

        // For now, let's just clamp the Opponent AVG.
        // No pitcher, no matter how tired, should have an Opponent AVG > .500 in the sim logic
        // or it breaks the Log5 math (resulting in infinite hits).

        // Cap at .450 (very bad, but still 55% chance of out)
        if ( $baseAvg > 0.450 ) {
            return 0.450;
        }

        return $baseAvg;
    }

    /**
     * @param $batter
     */
    private function determineHitType( $batter )
    {
        $h = $batter['H'] ?? 1;
        if ( $h == 0 ) {
            $h = 1;
        }

        $r2b = ( $batter['2B'] ?? 0 ) / $h;
        $r3b = ( $batter['3B'] ?? 0 ) / $h;

        $roll = mt_rand( 0, 1000 ) / 1000;

        if ( $roll < $r2b ) {
            return ['event' => self::RESULT_2B, 'desc' => 'Double'];
        }

        if ( $roll < ( $r2b + $r3b ) ) {
            return ['event' => self::RESULT_3B, 'desc' => 'Triple'];
        }

        return ['event' => self::RESULT_1B, 'desc' => 'Single'];
    }

    /**
     * @param $data
     * @param $key
     * @return int
     */
    private function getStat( $data, $key )
    {
        if ( isset( $data[$key] ) ) {
            return (float) $data[$key];
        }

        if ( isset( $data[strtoupper( $key )] ) ) {
            return (float) $data[strtoupper( $key )];
        }

        if ( isset( $data[strtolower( $key )] ) ) {
            return (float) $data[strtolower( $key )];
        }

        return 0;
    }
}
