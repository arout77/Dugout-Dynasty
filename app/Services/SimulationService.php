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
        // 1. Get Context
        $bYear = (int) ( $batter['YR'] ?? $batter['Year'] ?? 2024 );
        $pYear = (int) ( $pitcher['YR'] ?? $pitcher['Year'] ?? 2024 );

        $bLeague = $this->leagueStats->getStatsForYear( $bYear );
        $pLeague = $this->leagueStats->getStatsForYear( $pYear );

        // Baseline Environment (Use modern era approx if we want "neutral" play,
        // or use the pitcher's era as the "home field" context.
        // Standard practice: Use the PITCHER'S league as the base environment.)
        $env = $pLeague;

        // 2. Calculate Probabilities for Key Events
        // We calculate the probability of specific "Three True Outcomes" + Hits

        // --- WALKS (BB) ---
        // Batter BB% = Batter BB / Batter PA
        // Pitcher BB% = Pitcher BB / Pitcher Batters Faced (approx IP*4.2 or similar, or use BB9/38)
        // For simplicity and speed, we use the Rate Stats if we calculated them, or derive them.

        $probBB = $this->calcLog5(
            $this->getRate( $batter, 'BB', 'PA' ),
            $this->getRate( $pitcher, 'BB', 'BF' ),
            $env['avg_bb_pa'] ?? 0.08
        );

        // --- STRIKEOUTS (SO) ---
        $probSO = $this->calcLog5(
            $this->getRate( $batter, 'SO', 'PA' ),
            $this->getRate( $pitcher, 'SO', 'BF' ),
            $env['avg_so_pa'] ?? 0.20
        );

        // --- HOME RUNS (HR) ---
        $probHR = $this->calcLog5(
            $this->getRate( $batter, 'HR', 'PA' ),
            $this->getRate( $pitcher, 'HR', 'BF' ),
            $env['avg_hr_pa'] ?? 0.03
        );

        // --- HITS (AVG) ---
        // Note: Log5 for AVG is tricky because AVG excludes BB.
        // Better to calculate "Ball In Play Hit Probability" or just generic Hit Probability.
        // We'll use generic AVG for now as a proxy for "Hit Event".
        $probHit = $this->calcLog5(
            $batter['AVG'] ?? $batter['BA'] ?? .250,
            $this->deriveOpponentAVG( $pitcher ), // Pitchers don't always have AVG allowed column
            $env['avg_ba'] ?? .250
        );

        // 3. Normalize Outcomes to 1.0 scale
        // A simpler approach for game engines: Check distinct events in hierarchical order.

        $roll = mt_rand( 0, 1000 ) / 1000;

        // Check Walk
        if ( $roll < $probBB ) {
            return ['event' => self::RESULT_BB, 'desc' => 'Walk'];
        }

        $roll -= $probBB;

        // Check Strikeout
        if ( $roll < $probSO ) {
            return ['event' => self::RESULT_SO, 'desc' => 'Strikeout'];
        }

        $roll -= $probSO;

        // Check Home Run
        if ( $roll < $probHR ) {
            return ['event' => self::RESULT_HR, 'desc' => 'Home Run'];
        }

        $roll -= $probHR;

        // Check General Hit (Singles/Doubles/Triples)
        // We use the remaining probability space.
        // If Prob(Hit) is .250, that means 25% of ALL PAs are hits.
        // We already accounted for HRs. So remaining Hits = Prob(Hit) - Prob(HR).
        $probBaseHit = max( 0, $probHit - $probHR );

        if ( $roll < $probBaseHit ) {
            // It's a non-HR hit. Determine type based on batter's ISO or history.
            return $this->determineHitType( $batter );
        }

        // If we are here, it's an Out (Ball in Play)
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
        $val = $player[$stat] ?? 0;
        $den = 1;

        if ( $denominator === 'PA' ) {
            // Approx PA if not in DB: AB + BB + HBP + SF
            $den = ( $player['AB'] ?? 1 ) + ( $player['BB'] ?? 0 );
        } elseif ( $denominator === 'BF' ) {
            // Approx Batters Faced: IP * 2.9 + H + BB
            $ip  = $player['IP'] ?? 1;
            $den = ( $ip * 2.9 ) + ( $player['H'] ?? 0 ) + ( $player['BB'] ?? 0 );
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
}
