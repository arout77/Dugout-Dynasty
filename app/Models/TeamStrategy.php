<?php

namespace App\Models;

use Core\BaseModel;
use PDO;

class TeamStrategy extends BaseModel
{
    /**
     * Save a complete strategy set (rotation or lineup) for a team.
     * Clears existing entries for that type to avoid conflicts.
     */
    public function saveStrategy( int $teamId, string $type, array $items )
    {
        $this->db->beginTransaction();

        try {
            // 1. Clear existing strategy of this type
            $stmt = $this->db->prepare( "DELETE FROM team_strategies WHERE team_id = :tid AND type = :type" );
            $stmt->execute( [':tid' => $teamId, ':type' => $type] );

            // 2. Insert new items
            $sql  = "INSERT INTO team_strategies (team_id, type, slot, player_id, position) VALUES (:tid, :type, :slot, :pid, :pos)";
            $stmt = $this->db->prepare( $sql );

            foreach ( $items as $item ) {
                $stmt->execute( [
                    ':tid'  => $teamId,
                    ':type' => $type,
                    ':slot' => $item['slot'],
                    ':pid'  => $item['player_id'],
                    ':pos'  => $item['pos'] ?? null, // Position is optional for rotation
                ] );
            }

            $this->db->commit();
            return true;
        } catch ( \Exception $e ) {
            $this->db->rollBack();
            error_log( "Strategy Save Failed: " . $e->getMessage() );
            return false;
        }
    }

    /**
     * @param int $teamId
     * @param string $type
     * @return mixed
     */
    public function getStrategy( int $teamId, string $type ): array
    {
        $sql = "SELECT s.*, r.player_name
                FROM team_strategies s
                JOIN rosters r ON s.player_id = r.player_id AND r.team_id = s.team_id
                WHERE s.team_id = :tid AND s.type = :type
                ORDER BY s.slot ASC";

        $stmt = $this->db->prepare( $sql );
        $stmt->execute( [':tid' => $teamId, ':type' => $type] );
        return $stmt->fetchAll( PDO::FETCH_ASSOC );
    }
}
