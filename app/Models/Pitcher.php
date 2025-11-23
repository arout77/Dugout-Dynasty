<?php

namespace App\Models;

use Core\BaseModel;
use PDO;

class Pitcher extends BaseModel
{
    /**
     * @param int $limit
     * @param int $offset
     * @param string $sortBy
     * @param string $direction
     * @param array $filters
     * @return mixed
     */
    public function findAvailable( int $limit, int $offset, string $sortBy = 'PRICE', string $direction = 'DESC', array $filters = [] ): array
    {
        $allowedSorts = [
            'PRICE', 'NAME', 'YR',
            'G', 'GS', 'CG', 'SHO', 'W', 'L', 'IP', 'H', 'SO', 'K', 'BB', 'HR', 'ERA', 'WHIP',
        ];

        if ( !in_array( $sortBy, $allowedSorts ) ) {
            $sortBy = 'PRICE';
        }

        $sql = "SELECT * FROM pitchers
                WHERE NAME NOT IN (SELECT player_name FROM rosters) ";

        if ( !empty( $filters['role'] ) ) {
            if ( $filters['role'] === 'SP' ) {
                $sql .= " AND Endurance LIKE 'S%' ";
            } elseif ( $filters['role'] === 'RP' ) {
                $sql .= " AND (Endurance LIKE 'R%' OR Endurance LIKE 'C%') ";
            }
        }

        // NEW: Search Filter
        if ( !empty( $filters['search'] ) ) {
            $sql .= " AND NAME LIKE :search ";
        }

        if ( $sortBy === 'PRICE' ) {
            $sql .= " ORDER BY CAST(REPLACE(PRICE, ',', '') AS UNSIGNED) $direction ";
        } else {
            if ( $sortBy === 'SO' ) {
                $sortBy = 'K';
            }

            $sql .= " ORDER BY $sortBy $direction ";
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare( $sql );
        $stmt->bindValue( ':limit', $limit, PDO::PARAM_INT );
        $stmt->bindValue( ':offset', $offset, PDO::PARAM_INT );

        if ( !empty( $filters['search'] ) ) {
            $stmt->bindValue( ':search', '%' . $filters['search'] . '%' );
        }

        $stmt->execute();

        return $stmt->fetchAll( PDO::FETCH_ASSOC );
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function findById( int $id ): ?array
    {
        $stmt = $this->db->prepare( "SELECT * FROM pitchers WHERE ID = :id" );
        $stmt->execute( [':id' => $id] );
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find by Unique Row ID
     */
    public function findByRowId( int $rowId ): ?array
    {
        $stmt = $this->db->prepare( "SELECT * FROM pitchers WHERE row_id = :id" );
        $stmt->execute( [':id' => $rowId] );
        $result = $stmt->fetch( PDO::FETCH_ASSOC );
        return $result ?: null;
    }
}
