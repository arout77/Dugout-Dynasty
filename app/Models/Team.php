<?php

namespace App\Models;

use Core\BaseModel;
use PDO;

class Team extends BaseModel
{
    // Archetype Constants for easy reference in code
    const ARCHETYPE_STEINBRENNER = 'Steinbrenner'; // Star Hunter
    const ARCHETYPE_MONEYBALL    = 'Moneyball'; // Value Seeker
    const ARCHETYPE_BALANCED     = 'Balanced'; // Positional Weighting

    // Trait Constants
    const TRAIT_SMALL_BALL = 'SmallBall';
    const TRAIT_POWER      = 'Power';
    const TRAIT_DEFENSE    = 'Defense';
    const TRAIT_ANALYTICS  = 'Analytics';

    /**
     * Creates a new team.
     */
    public function create( array $data ): int
    {
        $sql = "INSERT INTO teams (team_name, is_user_controlled, salary_cap, draft_order, spending_archetype, scouting_trait)
                VALUES (:name, :is_user, :cap, :order, :archetype, :trait)";

        $stmt = $this->db->prepare( $sql );
        $stmt->execute( [
            ':name'      => $data['name'],
            ':is_user'   => $data['is_user_controlled'] ?? 0,
            ':cap'       => $data['salary_cap'] ?? 80000000,
            ':order'     => $data['draft_order'] ?? 0,
            ':archetype' => $data['spending_archetype'] ?? self::ARCHETYPE_BALANCED,
            ':trait'     => $data['scouting_trait'] ?? self::TRAIT_ANALYTICS,
        ] );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Findings a team by ID.
     */
    public function findById( int $id ): ?array
    {
        $stmt = $this->db->prepare( "SELECT * FROM teams WHERE team_id = :id" );
        $stmt->execute( [':id' => $id] );
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Returns all teams sorted by draft order.
     */
    public function getAllByDraftOrder(): array
    {
        return $this->db->query( "SELECT * FROM teams ORDER BY draft_order ASC" )->fetchAll();
    }

    /**
     * Updates the remaining salary cap after a pick.
     */
    public function deductCapSpace( int $teamId, int $amount ): bool
    {
        $sql  = "UPDATE teams SET salary_cap = salary_cap - :amount WHERE team_id = :id";
        $stmt = $this->db->prepare( $sql );
        return $stmt->execute( [':amount' => $amount, ':id' => $teamId] );
    }
}
