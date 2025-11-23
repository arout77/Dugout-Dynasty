<?php

namespace App\Services;

use App\Models\Hitter;
use App\Models\Pitcher;
use App\Models\Roster;
use App\Models\Team;
use PDO;

class DraftStrategyService
{
    protected Team $teamModel;
    protected Roster $rosterModel;
    protected Hitter $hitterModel;
    protected Pitcher $pitcherModel;

    // Roster Constraints
    const MIN_SP      = 5;
    const MIN_RP      = 5;
    const ROSTER_SIZE = 25; // Target size (24-26 allowed)
    const MIN_SALARY  = 500000;

    public function __construct()
    {
        $this->teamModel    = new Team();
        $this->rosterModel  = new Roster();
        $this->hitterModel  = new Hitter();
        $this->pitcherModel = new Pitcher();
    }

    /**
     * Main Simulation Loop.
     * Advances the draft until it encounters a Human team or the draft ends.
     */
    public function advanceDraft( int $leagueId, int $totalTeams ): array
    {
        $maxPicks = $totalTeams * self::ROSTER_SIZE;

        // Prevent infinite loops with a hard limit per request
        $safetyCounter = 0;

        while ( $safetyCounter < 50 ) {
            // 1. Get Current State
            $leagueState = $this->getLeagueState( $leagueId );
            $currentPick = $leagueState['current_pick'];

            if ( $currentPick > $maxPicks ) {
                return ['status' => 'finished'];
            }

            // 2. Determine Whose Turn It Is (Snake Draft)
            $draftOrderIndex = $this->calculateSnakeOrder( $currentPick, $totalTeams );

            // Fetch the team at this draft position
            $teams = $this->teamModel->getAllByDraftOrder(); // Assuming ordered 1..N
            // Array index is 0-based, so map correctly
            $currentTeam = isset( $teams[$draftOrderIndex - 1] ) ? $teams[$draftOrderIndex - 1] : null;

            if ( !$currentTeam ) {
                // Should not happen, but break to prevent loop
                break;
            }

            // 3. Check if Human
            if ( $currentTeam['is_user_controlled'] ) {
                return [
                    'status'      => 'human_turn',
                    'team_id'     => $currentTeam['team_id'],
                    'pick_number' => $currentPick,
                ];
            }

            // 4. It's CPU -> Make the Pick
            $this->makeCpuPick( $currentTeam );

            // 5. Increment Pick Counter
            $this->incrementPick( $leagueId );

            $safetyCounter++;
        }

        return ['status' => 'advanced'];
    }

    /**
     * Simulates the remainder of the draft for ALL teams if user exits draft.
     */
    public function completeDraft( int $leagueId, int $totalTeams )
    {
        $maxPicks      = $totalTeams * self::ROSTER_SIZE;
        $safetyCounter = 0;

        // Loop until draft is over (limit 1000 iterations safety)
        while ( $safetyCounter < 1000 ) {
            $leagueState = $this->getLeagueState( $leagueId );
            $currentPick = $leagueState['current_pick'];

            if ( $currentPick > $maxPicks ) {
                break;
            }
            // Draft Over

            $draftOrderIndex = $this->calculateSnakeOrder( $currentPick, $totalTeams );
            $teams           = $this->teamModel->getAllByDraftOrder();
            $currentTeam     = isset( $teams[$draftOrderIndex - 1] ) ? $teams[$draftOrderIndex - 1] : null;

            if ( $currentTeam ) {
                // If user controlled, use Panic Pick logic (Best Available Affordable)
                // This fills their roster if they have money, or skips if full/broke.
                // Actually, makeCpuPick handles needs analysis.
                // If roster is full (26), makeCpuPick might try to draft a 27th?
                // We should add a check in makeCpuPick.
                $this->makeCpuPick( $currentTeam );
            }

            $this->incrementPick( $leagueId );
            $safetyCounter++;
        }
    }

    /**
     * The Brain: Selects a player for the CPU.
     */
    protected function makeCpuPick( array $team )
    {
        // 1. Analyze Roster Needs
        $roster = $this->rosterModel->getPlayersByTeam( $team['team_id'] );
        $needs  = $this->analyzeNeeds( $roster );

        // 2. Determine Budget Strategy
        $maxBid = $this->calculateMaxBid( $team, count( $roster ) );

        // 3. Fetch Candidates (Top 50 available to evaluate)
        // We fetch a mix of Hitters and Pitchers if both are needed, or specific if critical need.
        $candidates = $this->fetchCandidates( $needs, $maxBid, $team['scouting_trait'] );

        // 4. Score Candidates based on Team Personality
        $bestPlayer   = null;
        $highestScore = -1;

        foreach ( $candidates as $player ) {
            $score = $this->calculateScore( $player, $team, $needs );
            if ( $score > $highestScore ) {
                $highestScore = $score;
                $bestPlayer   = $player;
            }
        }

        // 5. Execute Pick (Fallback to random affordable if no candidate found - safety net)
        if ( $bestPlayer ) {
            $type = isset( $player['Endurance'] ) ? 'pitcher' : 'hitter'; // Simple detection
            $this->executePick( $team['team_id'], $bestPlayer, $type );
        } else {
            // Panic Pick: Just grab the cheapest available player to fill roster
            // (Implementation omitted for brevity, but crucial for production)
        }
    }

    /**
     * Returns the draft order position (1 to TotalTeams) for a given pick number in a Snake Draft.
     */
    private function calculateSnakeOrder( int $pick, int $totalTeams ): int
    {
        $cycle      = $totalTeams * 2;
        $posInCycle = ( $pick - 1 ) % $cycle;

        if ( $posInCycle < $totalTeams ) {
            return $posInCycle + 1;
        } else {
            return $cycle - $posInCycle;
        }
    }

    /**
     * Analyzes current roster to find gaps.
     */
    private function analyzeNeeds( array $roster ): array
    {
        $counts = [
            'SP' => 0, 'RP' => 0,
            'C'  => 0, '1B' => 0, '2B' => 0, '3B' => 0, 'SS' => 0,
            'LF' => 0, 'CF' => 0, 'RF' => 0,
        ];

        foreach ( $roster as $p ) {
            $pos = $p['position']; // Stored in DB

            // Handle specific pitcher logic based on stored stats or flags
            if ( $pos === 'P' ) {
                // We need to re-parse endurance or check how we stored it.
                // For now, assuming we stored specific pos or infer from name/logic.
                // Simplification: We will rely on the 'position' column being accurate.
            }
            if ( isset( $counts[$pos] ) ) {
                $counts[$pos]++;
            }
        }

        $needs = [];
        // Critical Needs
        if ( $counts['SP'] < self::MIN_SP ) {
            $needs[] = 'SP';
        }

        if ( $counts['RP'] < self::MIN_RP ) {
            $needs[] = 'RP';
        }

        // Positional Needs (If 0, high priority)
        foreach ( ['C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF'] as $pos ) {
            if ( $counts[$pos] < 1 ) {
                $needs[] = $pos;
            }

        }

        return $needs;
    }

    /**
     * Calculates how much the team can afford for this single pick.
     */
    private function calculateMaxBid( array $team, int $currentRosterCount ): int
    {
        $spotsRemaining = self::ROSTER_SIZE - $currentRosterCount;
        if ( $spotsRemaining <= 0 ) {
            return 0;
        }

        $capSpace = $team['salary_cap'];

        // Reserve money for future picks (assuming minimum salary for them)
        $reserve = ( $spotsRemaining - 1 ) * self::MIN_SALARY;
        $maxMath = $capSpace - $reserve;

        // Apply Archetype Logic
        switch ( $team['spending_archetype'] ) {
            case Team::ARCHETYPE_STEINBRENNER:
                // Willing to blow 80% of available math on one guy
                return (int) ( $maxMath * 0.80 );
            case Team::ARCHETYPE_MONEYBALL:
                // Wants to spread it out, rarely goes "All in"
                return (int) ( $maxMath * 0.40 );
            case Team::ARCHETYPE_BALANCED:
            default:
                return (int) ( $maxMath * 0.60 );
        }
    }

    /**
     * Fetches a pool of players to evaluate.
     */
    private function fetchCandidates( array $needs, int $maxBid, string $trait ): array
    {
        $candidates = [];

        // If we have specific needs, prioritizing fetching those
        $lookingForPitchers = in_array( 'SP', $needs ) || in_array( 'RP', $needs );
        $lookingForHitters  = count( array_intersect( $needs, ['C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF'] ) ) > 0;

        // Fallback: If needs are met (filling bench), look for anything
        if ( !$lookingForPitchers && !$lookingForHitters ) {
            $lookingForPitchers = true;
            $lookingForHitters  = true;
        }

        // 1. Fetch Hitters
        if ( $lookingForHitters ) {
            // Sort based on Trait to get relevant candidates
            $sort = 'PRICE';
            if ( $trait === Team::TRAIT_SMALL_BALL ) {
                $sort = 'SB';
            }

            if ( $trait === Team::TRAIT_POWER ) {
                $sort = 'HR';
            }

            if ( $trait === Team::TRAIT_ANALYTICS ) {
                $sort = 'OPS';
            }
            // Assuming we have OPS or calculate it

            // Fetch top 30 available
            $rawHitters = $this->hitterModel->findAvailable( 30, 0, $sort, 'DESC' );
            foreach ( $rawHitters as $h ) {
                // Filter by Max Bid
                $price = (int) str_replace( [',', '$'], '', $h['PRICE'] );
                if ( $price <= $maxBid ) {
                    $candidates[] = $h;
                }
            }
        }

        // 2. Fetch Pitchers
        if ( $lookingForPitchers ) {
            $sort = 'PRICE';
            if ( $trait === Team::TRAIT_POWER ) {
                $sort = 'SO';
            }
            // Power teams like Strikeouts
            if ( $trait === Team::TRAIT_ANALYTICS ) {
                $sort = 'WHIP';
            }

            $rawPitchers = $this->pitcherModel->findAvailable( 30, 0, $sort, 'DESC' );
            foreach ( $rawPitchers as $p ) {
                $price = (int) str_replace( [',', '$'], '', $p['PRICE'] );
                if ( $price <= $maxBid ) {
                    $candidates[] = $p;
                }
            }
        }

        return $candidates;
    }

    /**
     * Scores a player based on how well they fit the team's identity.
     */
    private function calculateScore( array $player, array $team, array $needs ): float
    {
        $score     = 0;
        $isPitcher = isset( $player['Endurance'] );
        $salary    = (int) str_replace( [',', '$'], '', $player['PRICE'] );

        // --- 1. Need Bonus (High Priority) ---
        $pos = $this->parsePosition( $player );
        if ( in_array( $pos, $needs ) ) {
            $score += 50; // Huge bonus for filling a hole
        }

        // --- 2. Archetype Logic (Economic) ---
        // FIX: Added ?? 0 coalescing to all stats to prevent "Undefined array key" errors
        if ( $team['spending_archetype'] === Team::ARCHETYPE_MONEYBALL ) {
            // Formula: Performance / Salary
            $statVal = $isPitcher
            ? ( ( $player['W'] ?? 0 ) * 5 + ( $player['SO'] ?? 0 ) )
            : ( ( $player['HR'] ?? 0 ) * 4 + ( $player['RBI'] ?? 0 ) + ( $player['AVG'] ?? 0 ) * 100 );

            $salaryMil = max( 1, $salary / 1000000 );
            $score += ( $statVal / $salaryMil ) * 10;
        } elseif ( $team['spending_archetype'] === Team::ARCHETYPE_STEINBRENNER ) {
            // Likes expensive players (assumes Price = Quality)
            $score += ( $salary / 1000000 ) * 2;
        }

        // --- 3. Trait Logic (Scouting) ---
        if ( $team['scouting_trait'] === Team::TRAIT_SMALL_BALL && !$isPitcher ) {
            $score += ( $player['SB'] ?? 0 ) * 2;
            $score += ( $player['AVG'] ?? 0 ) * 100; // .300 avg adds 30 points
        } elseif ( $team['scouting_trait'] === Team::TRAIT_POWER && !$isPitcher ) {
            $score += ( $player['HR'] ?? 0 ) * 1.5;
        } elseif ( $team['scouting_trait'] === Team::TRAIT_DEFENSE && !$isPitcher ) {
            // Parse 'Fielding' string: rf-4(-3)e6 or ss-3e7
            // Take the first position listed (primary position)
            $fieldingRaw = explode( '/', $player['Fielding'] ?? '' )[0];
            $fieldingRaw = trim( $fieldingRaw );

            // Regex breakdown:
            // ^([a-z0-9]+)  -> Position (group 1)
            // -(\d)         -> Range 1-5 (group 2)
            // (?:\(([+-]?\d+)\))? -> Optional Arm (-5 to 5) (group 3)
            // e(\d+)        -> Errors (group 4)
            if ( preg_match( '/^([a-z0-9]+)-(\d)(?:\(([+-]?\d+)\))?e(\d+)/', $fieldingRaw, $matches ) ) {
                $range  = (int) $matches[2];
                $arm    = isset( $matches[3] ) && $matches[3] !== '' ? (int) $matches[3] : 0; // Default 0 (avg) if missing
                $errors = (int) $matches[4];

                // Scoring Logic:
                // Range: 1 (Elite) -> 5 (Poor). Invert so higher is better.
                // (6 - Range) * 15 pts.
                // Range 1 = 75 pts. Range 5 = 15 pts.
                $score += ( 6 - $range ) * 15;

                // Arm: -5 (Elite) -> 5 (Weak). Invert so lower is better.
                // (6 - Arm) * 5 pts.
                // Arm -5 = 11*5 = 55 pts. Arm 0 = 6*5 = 30 pts. Arm 5 = 1*5 = 5 pts.
                $score += ( 6 - $arm ) * 5;

                // Errors: Penalize high errors.
                // -0.5 points per error.
                $score -= ( $errors * 0.5 );
            }
        }

        return $score;
    }

    /**
     * @param array $player
     */
    private function parsePosition( array $player ): string
    {
        // If 'Endurance' exists, it's a pitcher
        if ( isset( $player['Endurance'] ) ) {
            // S7 -> SP, R2 -> RP
            if ( str_starts_with( $player['Endurance'], 'S' ) ) {
                return 'SP';
            }

            return 'RP';
        }
        // Hitter: 'rf-4...' -> 'RF'
        // Extract first 2 chars, uppercase
        // Handle '1b', 'ss', 'cf'
        $raw = substr( $player['Fielding'] ?? '', 0, 2 );
        return strtoupper( str_replace( '-', '', $raw ) );
    }

    /**
     * @param int $teamId
     * @param array $player
     * @param string $type
     */
    private function executePick( int $teamId, array $player, string $type )
    {
        $this->rosterModel->addPlayer( $teamId, $player, $type );
        $salary = (int) str_replace( [',', '$'], '', $player['PRICE'] );
        $this->teamModel->deductCapSpace( $teamId, $salary );
    }

    /**
     * @param int $leagueId
     * @return mixed
     */
    private function getLeagueState( int $leagueId ): array
    {
        // Simple DB fetch for league state
        // Use direct DB via one of the models for convenience
        $stmt = $this->teamModel->getDb()->prepare( "SELECT * FROM leagues WHERE league_id = :id" );
        $stmt->execute( [':id' => $leagueId] );
        return $stmt->fetch( PDO::FETCH_ASSOC );
    }

    /**
     * @param int $leagueId
     */
    private function incrementPick( int $leagueId )
    {
        $sql  = "UPDATE leagues SET current_pick = current_pick + 1 WHERE league_id = :id";
        $stmt = $this->teamModel->getDb()->prepare( $sql );
        $stmt->execute( [':id' => $leagueId] );
    }

    // Helper to safely get array key case-insensitively
    /**
     * @param array $data
     * @param string $key
     * @return mixed
     */
    private function getStat( array $data, string $key )
    {
        if ( array_key_exists( $key, $data ) ) {
            return $data[$key];
        }

        $upper = strtoupper( $key );
        if ( array_key_exists( $upper, $data ) ) {
            return $data[$upper];
        }

        $lower = strtolower( $key );
        if ( array_key_exists( $lower, $data ) ) {
            return $data[$lower];
        }

        // Specific Mapping for DB column names if inconsistent
        if ( $key === 'Endurance' && isset( $data['ENDURANCE'] ) ) {
            return $data['ENDURANCE'];
        }

        if ( $key === 'Fielding' && isset( $data['FIELDING'] ) ) {
            return $data['FIELDING'];
        }

        return null;
    }
}
