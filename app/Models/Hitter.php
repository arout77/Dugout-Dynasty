<?php

namespace App\Models;

use Core\BaseModel;
use PDO;

class Hitter extends BaseModel
{
    /**
     * Fetches available hitters, excluding anyone whose NAME appears in the rosters table.
     * Handles sorting and pagination.
     *
     * @param int $limit Number of records
     * @param int $offset Pagination offset
     * @param string $sortBy Column to sort by (default: PRICE)
     * @param string $direction ASC or DESC (default: DESC)
     * @param array $filters Optional filters (e.g., specific position)
     */
    public function findAvailable( int $limit, int $offset, string $sortBy = 'PRICE', string $direction = 'DESC', array $filters = [] ): array
    {
        $allowedSorts = [
            'PRICE', 'NAME', 'YR',
            'AB', 'R', 'H', '2B', '3B', 'HR', 'RBI', 'SO', 'K', 'BB', 'AVG', 'BA', 'OBP', 'SLG', 'SB',
        ];

        if ( !in_array( $sortBy, $allowedSorts ) ) {
            $sortBy = 'PRICE';
        }

        $sql = "SELECT * FROM hitters
                WHERE NAME NOT IN (SELECT player_name FROM rosters) ";

        if ( !empty( $filters['pos'] ) && $filters['pos'] !== 'all' ) {
            $sql .= " AND Fielding REGEXP :pos_pattern ";
        }

        // NEW: Improved Search
        if ( !empty( $filters['search'] ) ) {
            $sql .= " AND (NAME LIKE :search OR NAME LIKE :search_rev) ";
        }

        if ( $sortBy === 'PRICE' ) {
            $sql .= " ORDER BY CAST(REPLACE(PRICE, ',', '') AS UNSIGNED) $direction ";
        } else {
            if ( $sortBy === 'K' ) {
                $sortBy = 'SO';
            }

            $sql .= " ORDER BY $sortBy $direction ";
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare( $sql );
        $stmt->bindValue( ':limit', $limit, PDO::PARAM_INT );
        $stmt->bindValue( ':offset', $offset, PDO::PARAM_INT );

        if ( !empty( $filters['pos'] ) && $filters['pos'] !== 'all' ) {
            $p       = strtolower( $filters['pos'] );
            $pattern = "(^|/)\s*$p-";
            $stmt->bindValue( ':pos_pattern', $pattern );
        }

        if ( !empty( $filters['search'] ) ) {
            $s = $filters['search'];
            $stmt->bindValue( ':search', "%$s%" );
            // Also try "First Last" if DB is "Last, First"
            $stmt->bindValue( ':search_rev', "%$s%" );
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
        $stmt = $this->db->prepare( "SELECT * FROM hitters WHERE ID = :id" );
        $stmt->execute( [':id' => $id] );
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find by Unique Row ID (Primary Key)
     */
    public function findByRowId( int $rowId ): ?array
    {
        $stmt = $this->db->prepare( "SELECT * FROM hitters WHERE row_id = :id" );
        $stmt->execute( [':id' => $rowId] );
        $result = $stmt->fetch( PDO::FETCH_ASSOC );
        return $result ?: null;
    }
}
