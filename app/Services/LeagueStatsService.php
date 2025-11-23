<?php

namespace App\Services;

use Core\Database;
use PDO;

class LeagueStatsService
{
    protected PDO $db;
    protected array $cache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get the league averages for a specific year.
     * * @param int $year The historical season (e.g., 1927)
     * @return array|null Associative array of stats or null if not found
     */
    public function getStatsForYear( int $year ): ?array
    {
        // 1. Check Memory Cache first (Simulation runs thousands of times, minimize DB hits)
        if ( isset( $this->cache[$year] ) ) {
            return $this->cache[$year];
        }

        // 2. Fetch from Database
        $stmt = $this->db->prepare( "SELECT * FROM league_stats WHERE year = :year LIMIT 1" );
        $stmt->execute( [':year' => $year] );
        $stats = $stmt->fetch( PDO::FETCH_ASSOC );

        if ( $stats ) {
            // Cast to float for math safety
            foreach ( $stats as $key => $val ) {
                if ( $key !== 'year' && $key !== 'year_id' ) {
                    $stats[$key] = (float) $val;
                }
            }
            $this->cache[$year] = $stats;
            return $stats;
        }

        // 3. Fallback: Return "Modern Era" default if historical data is missing
        // This prevents the simulation from crashing on missing years.
        $defaults = [
            'year'     => $year,
            'avg_k_9'  => 8.50,
            'avg_bb_9' => 3.20,
            'avg_hr_9' => 1.10,
            'avg_era'  => 4.00,
            'avg_whip' => 1.300,
            'avg_ba'   => 0.250,
            'avg_obp'  => 0.320,
            'avg_slg'  => 0.410,
        ];

        // Cache the default so we don't query the DB again for this missing year
        $this->cache[$year] = $defaults;
        return $defaults;
    }

    /**
     * Batch import stats (Useful for seeding the table from your SQL dump or CSV)
     */
    public function importStats( array $rows )
    {
        $sql = "INSERT INTO league_stats (year, avg_k_9, avg_bb_9, avg_hr_9, avg_era, avg_whip, avg_ba, avg_obp, avg_slg)
                VALUES (:year, :k9, :bb9, :hr9, :era, :whip, :ba, :obp, :slg)
                ON DUPLICATE KEY UPDATE
                avg_k_9 = VALUES(avg_k_9), avg_bb_9 = VALUES(avg_bb_9), avg_era = VALUES(avg_era)";

        $stmt = $this->db->prepare( $sql );

        foreach ( $rows as $row ) {
            $stmt->execute( [
                ':year' => $row['year'],
                ':k9'   => $row['k_9'] ?? null,
                ':bb9'  => $row['bb_9'] ?? null,
                ':hr9'  => $row['hr_9'] ?? null,
                ':era'  => $row['era'] ?? null,
                ':whip' => $row['whip'] ?? null,
                ':ba'   => $row['ba'] ?? null,
                ':obp'  => $row['obp'] ?? null,
                ':slg'  => $row['slg'] ?? null,
            ] );
        }
    }
}
