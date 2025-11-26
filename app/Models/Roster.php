<?php

namespace App\Models;

use Core\BaseModel;
use PDO;

class Roster extends BaseModel
{
    /**
     * @param int $teamId
     * @param array $playerData
     * @param string $positionType
     * @return mixed
     */
    public function addPlayer( int $teamId, array $playerData, string $positionType ): bool
    {
        $sql = "INSERT INTO rosters (team_id, player_id, player_name, position, salary, season_year, stats_source)
                VALUES (:team_id, :pid, :name, :pos, :salary, :year, :source)";

        $id = $this->getAttribute( $playerData, 'row_id' ) ?? $this->getAttribute( $playerData, 'ID' ) ?? $playerData['id'] ?? null;

        if ( !$id ) {
            return false;
        }

        $source = ( $positionType === 'pitcher' || isset( $playerData['PITCHER_ID'] ) ) ? 'pitchers' : 'hitters';
        $price  = $this->parseSalary( $this->getAttribute( $playerData, 'PRICE' ) ?? '0' );
        $name   = $this->getAttribute( $playerData, 'NAME' ) ?? 'Unknown';
        $year   = $this->getAttribute( $playerData, 'YR' ) ?? 0;

        // Determine Position
        $pos       = 'BENCH';
        $endurance = $this->getAttribute( $playerData, 'Endurance' );
        if ( $source === 'pitchers' || $endurance ) {
            $pos = ( strpos( $endurance ?? '', 'S' ) === 0 ) ? 'SP' : 'RP';
        } else {
            $fielding = $this->getAttribute( $playerData, 'Fielding' );
            if ( $fielding && preg_match( '/^\s*([a-z0-9]{1,2})-/i', $fielding, $matches ) ) {
                $pos = strtoupper( $matches[1] );
            }
        }

        $stmt = $this->db->prepare( $sql );
        return $stmt->execute( [
            ':team_id' => $teamId, ':pid' => $id, ':name'    => $name,
            ':pos'     => $pos, ':salary' => $price, ':year' => $year, ':source' => $source,
        ] );
    }

    /**
     * @param int $teamId
     */
    public function getCountByTeam( int $teamId ): int
    {
        $stmt = $this->db->prepare( "SELECT COUNT(*) FROM rosters WHERE team_id = :id" );
        $stmt->execute( [':id' => $teamId] );
        return (int) $stmt->fetchColumn();
    }

    /**
     * @param int $teamId
     * @return mixed
     */
    public function getPlayersByTeam( int $teamId ): array
    {
        $stmt = $this->db->prepare( "SELECT * FROM rosters WHERE team_id = :id" );
        $stmt->execute( [':id' => $teamId] );
        return $stmt->fetchAll( PDO::FETCH_ASSOC );
    }

    /**
     * NEW: Fetches roster AND joins the stats tables (hitters/pitchers).
     * This fixes the "No Hits" bug by ensuring AVG, ERA, etc. are present.
     */
    public function getPlayersByTeamWithStats( int $teamId ): array
    {
        // 1. Get the basic roster
        $roster = $this->getPlayersByTeam( $teamId );
        if ( empty( $roster ) ) {
            return [];
        }

        // 2. Separate IDs by source
        $hitterIds  = [];
        $pitcherIds = [];

        foreach ( $roster as $p ) {
            if ( ( $p['stats_source'] ?? '' ) === 'pitchers' ) {
                $pitcherIds[] = $p['player_id'];
            } else {
                $hitterIds[] = $p['player_id'];
            }
        }

        // 3. Fetch Stats
        $hitterStats = [];
        if ( !empty( $hitterIds ) ) {
            $in   = str_repeat( '?,', count( $hitterIds ) - 1 ) . '?';
            $stmt = $this->db->prepare( "SELECT * FROM hitters WHERE row_id IN ($in)" );
            $stmt->execute( $hitterIds );
            while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
                $hitterStats[$row['row_id']] = $row;
            }
        }

        $pitcherStats = [];
        if ( !empty( $pitcherIds ) ) {
            $in   = str_repeat( '?,', count( $pitcherIds ) - 1 ) . '?';
            $stmt = $this->db->prepare( "SELECT * FROM pitchers WHERE row_id IN ($in)" );
            $stmt->execute( $pitcherIds );
            while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
                $pitcherStats[$row['row_id']] = $row;
            }
        }

        // 4. Merge Stats into Roster Array
        foreach ( $roster as &$p ) {
            $pid = $p['player_id'];
            if ( ( $p['stats_source'] ?? '' ) === 'pitchers' ) {
                if ( isset( $pitcherStats[$pid] ) ) {
                    $p = array_merge( $p, $pitcherStats[$pid] );
                }
            } else {
                if ( isset( $hitterStats[$pid] ) ) {
                    $p = array_merge( $p, $hitterStats[$pid] );
                }
            }
        }

        return $roster;
    }

    /**
     * @param array $data
     * @param string $key
     * @return mixed
     */
    private function getAttribute( array $data, string $key )
    {
        if ( array_key_exists( $key, $data ) ) {
            return $data[$key];
        }

        $upper = strtoupper( $key );
        if ( array_key_exists( $upper, $data ) ) {
            return $data[$upper];
        }

        return null;
    }

    /**
     * @param string $salaryStr
     */
    private function parseSalary( string $salaryStr ): int
    {
        return (int) str_replace( [',', '$'], '', $salaryStr );
    }
}
