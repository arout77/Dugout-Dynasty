<?php

namespace App\Models;

use Core\BaseModel;
use PDO;

class Roster extends BaseModel
{
    /**
     * Adds a player to a specific team's roster.
     */
    public function addPlayer( int $teamId, array $playerData, string $positionType ): bool
    {
        $sql = "INSERT INTO rosters (team_id, player_id, player_name, position, salary, season_year, stats_source)
                VALUES (:team_id, :pid, :name, :pos, :salary, :year, :source)";

        // 1. Robust ID Retrieval: Prioritize Unique Row ID
        $id = $this->getAttribute( $playerData, 'row_id' ); // Check row_id first!

        // Fallbacks if row_id missing (legacy support)
        if ( $id === null ) {
            $id = $this->getAttribute( $playerData, 'ID' );
        }

        if ( $id === null && isset( $playerData['id'] ) ) {
            $id = $playerData['id'];
        }

        if ( $id === null ) {
            $id = $this->getAttribute( $playerData, 'PITCHER_ID' );
        }

        if ( $id === null ) {
            error_log( "Roster::addPlayer - ID missing. Data: " . print_r( $playerData, true ) );
            throw new \Exception( "Cannot draft player: Missing Player ID." );
        }

        $source = $positionType === 'pitcher' ? 'pitchers' : 'hitters';
        // Double check source based on keys present
        if ( $this->getAttribute( $playerData, 'PITCHER_ID' ) !== null ) {
            $source = 'pitchers';
        }

        $price  = $this->getAttribute( $playerData, 'PRICE' ) ?? '0';
        $salary = $this->parseSalary( $price );

        $name      = $this->getAttribute( $playerData, 'NAME' ) ?? 'Unknown';
        $year      = $this->getAttribute( $playerData, 'YR' ) ?? $this->getAttribute( $playerData, 'Year' ) ?? 0;
        $endurance = $this->getAttribute( $playerData, 'Endurance' );
        $fielding  = $this->getAttribute( $playerData, 'Fielding' );

        $pos = 'BENCH';
        if ( $source === 'pitchers' || $endurance ) {
            if ( $endurance ) {
                $pos = ( strpos( $endurance, 'S' ) === 0 ) ? 'SP' : 'RP';
            } else {
                $pos = 'P';
            }
        } else {
            if ( $fielding ) {
                if ( preg_match( '/^\s*([a-z0-9]{1,2})-/i', $fielding, $matches ) ) {
                    $rawPos         = strtoupper( $matches[1] );
                    $validPositions = ['C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'P'];
                    if ( in_array( $rawPos, $validPositions ) ) {
                        $pos = $rawPos;
                    }

                }
            }
        }

        $stmt = $this->db->prepare( $sql );
        return $stmt->execute( [
            ':team_id' => $teamId,
            ':pid'     => $id,
            ':name'    => $name,
            ':pos'     => $pos,
            ':salary'  => $salary,
            ':year'    => $year,
            ':source'  => $source,
        ] );
    }

    /**
     * Helper to fetch array value case-insensitively.
     */
    private function getAttribute( array $data, string $key )
    {
        // 1. Exact Match
        if ( array_key_exists( $key, $data ) ) {
            return $data[$key];
        }

        // 2. Uppercase Match (DB standard)
        $upper = strtoupper( $key );
        if ( array_key_exists( $upper, $data ) ) {
            return $data[$upper];
        }

        // 3. Lowercase Match
        $lower = strtolower( $key );
        if ( array_key_exists( $lower, $data ) ) {
            return $data[$lower];
        }

        // 4. Capitalized Match (Year)
        $ucfirst = ucfirst( strtolower( $key ) );
        if ( array_key_exists( $ucfirst, $data ) ) {
            return $data[$ucfirst];
        }

        return null;
    }

    /**
     * Checks if a specific player name has already been drafted.
     */
    public function isNameDrafted( string $playerName ): bool
    {
        $stmt = $this->db->prepare( "SELECT 1 FROM rosters WHERE player_name = :name LIMIT 1" );
        $stmt->execute( [':name' => $playerName] );
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Get current roster count for a team.
     */
    public function getCountByTeam( int $teamId ): int
    {
        $stmt = $this->db->prepare( "SELECT COUNT(*) FROM rosters WHERE team_id = :id" );
        $stmt->execute( [':id' => $teamId] );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get all players for a specific team.
     * This is the method the CPU needs to make decisions.
     */
    public function getPlayersByTeam( int $teamId ): array
    {
        $stmt = $this->db->prepare( "SELECT * FROM rosters WHERE team_id = :id" );
        $stmt->execute( [':id' => $teamId] );
        return $stmt->fetchAll( PDO::FETCH_ASSOC );
    }

    /**
     * Helper to clean the salary string.
     */
    private function parseSalary( string $salaryStr ): int
    {
        return (int) str_replace( [',', '$'], '', $salaryStr );
    }
}
