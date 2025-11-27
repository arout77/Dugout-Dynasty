<?php

namespace App\Services;

class PlayerEvaluationService
{
    /**
     * Calculates a 'Rating' (0-100) based on stats.
     * Incorporates Hitting, Pitching, and Defense.
     */
    public function calculateRating( $player, $type = 'hitter' )
    {
        if ( $type === 'hitter' ) {
            // 1. OFFENSIVE COMPONENT (OPS based)
            $obp = ( $player['H'] + $player['BB'] ) / ( $player['AB'] + $player['BB'] ?: 1 );
            $slg = ( ( $player['H'] - $player['2B'] - $player['3B'] - $player['HR'] ) + ( 2 * $player['2B'] ) + ( 3 * $player['3B'] ) + ( 4 * $player['HR'] ) ) / ( $player['AB'] ?: 1 );
            $ops = $obp + $slg;

            // Base Offensive Rating (0-100 scale, centered around .750 OPS)
            // .750 OPS -> 55 rating. 1.000 OPS -> 80 rating.
            $offRating = min( 99, max( 20, ( $ops * 100 ) - 20 ) );

            // 2. DEFENSIVE COMPONENT
            // Formula: (Fielding % * Range Factor) weighted by Position Difficulty

            // A. Fielding Percentage: (Chances - Errors) / Chances
            // We approximate 'Chances' as (Games * Avg Chances Per Game for Position)
            $pos        = $player['position'] ?? 'DH';
            $avgChances = $this->getAvgChances( $pos );
            // Use 'G' from stats if available, otherwise default to 1 to avoid div by zero
            $games      = $player['G'] ?? 1;
            $estChances = $games * $avgChances;

            $defRating = 50; // Start at average

            // If they haven't played much, assume average defense to avoid skewing
            if ( $estChances > 10 ) {
                $errors = $player['E'] ?? 0;
                // Fielding Percentage
                $fieldingPct = max( 0, ( $estChances - $errors ) / $estChances );

                // B. Position Weight (Shortstop is harder than 1B)
                $posWeight = $this->getPositionalWeight( $pos );

                // C. Calculate Defensive Score (Simple version)
                // Baseline: .980 is roughly average.
                // .990 is +10 pts raw -> +15 at SS.
                // .950 is -30 pts raw -> -45 at SS.
                $defScore = ( $fieldingPct - 0.980 ) * 1000;

                // Apply positional multiplier to the score variance
                $defRating = 50 + ( $defScore * $posWeight );
            }

            // 3. FINAL RATING (80% Offense, 20% Defense)
            // Adjust weights based on philosophy (e.g. SS/C defense matters more)
            // A DH has 0 defensive value in this calc if chances are 0, so we might want a floor.

            $finalRating = ( $offRating * 0.8 ) + ( $defRating * 0.2 );

            return min( 99, max( 10, $finalRating ) );

        } else {
            // Pitcher: ERA based
            $era = ( $player['ER'] * 9 ) / ( $player['IP'] ?: 1 );
            // 2.00 ERA = 99, 5.00 ERA = 60
            return min( 99, max( 40, 110 - ( $era * 10 ) ) );
        }
    }

    /**
     * @param $pos
     */
    private function getAvgChances( $pos )
    {
        return match ( $pos ) {
            'C'     => 7.0, '1B' => 9.0, '2B' => 4.5, 'SS' => 4.5,
            '3B'    => 3.0, 'LF' => 2.0, 'CF' => 2.5, 'RF' => 2.0,
            default => 0.0
        };
    }

    /**
     * @param $pos
     */
    private function getPositionalWeight( $pos )
    {
        // Multiplier for defensive value
        // SS/C/CF errors hurt more, plays made help more.
        return match ( $pos ) {
            'SS'    => 1.5, 'C'  => 1.4, '2B' => 1.2, 'CF' => 1.2,
            '3B'    => 1.0, 'RF' => 0.8, 'LF' => 0.8, '1B' => 0.5,
            default => 0.0
        };
    }
}
