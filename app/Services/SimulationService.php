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
     * Simulates a single Plate Appearance.
     * * @param array $batter  Hitter data (from DB)
     * @param array $pitcher Pitcher data (from DB)
     * @return array Result data ['event' => 'HR', 'log' => '...']
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
        // Safety floor: A major leaguer shouldn't have 0 chance to hit
        if ( $bAVG < 0.05 ) {
            $bAVG = 0.150;
        }

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
        // ProbHit is the probability of getting a hit in an AB (roughly).
        // We need to scale it because we are in the "Non-Three-True-Outcome" space.
        // Actually, standard Log5 gives P(Hit). If we subtracted BB/SO/HR already,
        // we need to see if the remaining roll falls into the Hit bucket.

        // Simple heuristic for MVP:
        // If ProbHit is .300, that's 300/1000.
        // We've used up, say, 300 points on BB/SO/HR. 700 remain.
        // We need to scale the hit probability to the remaining space? No.
        // Standard method: Define the ranges absolutely.
        // 0-BB
        // BB-(BB+SO)
        // (BB+SO)-(BB+SO+HR)
        // (BB+SO+HR)-(BB+SO+HR+Hit) <-- This is the hit range.

        if ( $roll < ( $probHit - $probHR ) ) { // Subtract HR because probHit usually includes HR
            return $this->determineHitType( $batter );
        }

        return ['event' => self::RESULT_OUT, 'desc' => 'Out'];
    }

    /**
     * The Log5 Formula.
     * P = (B*P*L) / ( ... )
     * actually the version for "League adjusted" is often:
     * P = (B * P / L) / ( (B * P / L) + ((1-B)*(1-P)/(1-L)) )
     */
    private function calcLog5( $b, $p, $l )
    {
        // Safety
        if ( $l <= 0 ) {
            $l = 0.01;
        }

        if ( $l >= 1 ) {
            $l = 0.99;
        }

        // Limit inputs to valid probability range
        $b = max( 0.001, min( 0.999, $b ) );
        $p = max( 0.001, min( 0.999, $p ) );

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
        }

        if ( $den == 0 ) {
            return 0;
        }

        return $val / $den;
    }

    /**
     * @param $pitcher
     * @return mixed
     */
    private function deriveOpponentAVG( $pitcher )
    {
        // If database has "OAVG" or similar, use it.
        // Otherwise estimate: (H / BF) approx?
        // Actually (H - HR) / (BF - BB - HR - SO) is BABIP.
        // Simple Opponent AVG = H / (BF - BB).

        $h  = $pitcher['H'] ?? 0;
        $bb = $pitcher['BB'] ?? 0;
        $ip = $pitcher['IP'] ?? 1;
        $bf = ( $ip * 2.9 ) + $h + $bb; // Rough estimate

        $ab = $bf - $bb;
        if ( $ab <= 0 ) {
            return .250;
        }

        return $h / $ab;
    }

    /**
     * @param $batter
     */
    private function determineHitType( $batter )
    {
        // Distribute hit types based on batter's history
        // Ratios relative to total hits
        $h = $batter['H'] ?? 1;
        if ( $h == 0 ) {
            $h = 1;
        }

        $r2b = ( $batter['2B'] ?? 0 ) / $h;
        $r3b = ( $batter['3B'] ?? 0 ) / $h;
        // 1B is the rest

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
