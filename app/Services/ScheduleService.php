<?php

namespace App\Services;

use App\Models\Team;
use Core\Database;
use DateInterval;
use DateTime;

class ScheduleService
{
    protected Team $teamModel;
    /**
     * @var mixed
     */
    protected $db;

    public function __construct()
    {
        $this->teamModel = new Team();
        $this->db        = Database::getInstance();
    }

    /**
     * @param int $leagueId
     * @return null
     */
    public function generateSchedule( int $leagueId )
    {
        // 1. Fetch Teams
        $stmt = $this->db->prepare( "SELECT team_id FROM teams WHERE league_id = :id" );
        $stmt->execute( [':id' => $leagueId] );
        $teams = $stmt->fetchAll( \PDO::FETCH_COLUMN );

        if ( count( $teams ) < 2 ) {
            return;
        }

        // 2. Setup Calendar (Last Thursday of March)
        $year = date( 'Y' );
        // Find last Thursday of March
        $date = new DateTime( "$year-03-31" );
        while ( $date->format( 'D' ) != 'Thu' ) {
            $date->sub( new DateInterval( 'P1D' ) );
        }

        // 3. Generate Round Robin Pairings
        // Simple algorithm: Rotate array of teams to create matchups
        $schedule = [];
        $numTeams = count( $teams );

        // If odd number of teams, add a "bye" (dummy team)
        if ( $numTeams % 2 != 0 ) {
            $teams[] = null;
            $numTeams++;
        }

        // Generate roughly 54 series (162 games / 3 games per series)
        // We loop through opponents
        $seriesCount  = 0;
        $targetSeries = 54;

        // Loop for Series Generation
        // We need enough rotations to fill 162 games.
        // A full round-robin is (N-1) series. For 12 teams, that's 11 series.
        // 11 series * 3 games = 33 games.
        // We need ~5 full round-robins to get to 162.

        for ( $cycle = 0; $cycle < 5; $cycle++ ) {
            // Standard Round Robin Rotation
            for ( $round = 0; $round < $numTeams - 1; $round++ ) {
                if ( $seriesCount >= $targetSeries ) {
                    break;
                }

                $seriesDate = clone $date;

                // Create Pairings for this "Week/Series"
                for ( $i = 0; $i < $numTeams / 2; $i++ ) {
                    $t1 = $teams[$i];
                    $t2 = $teams[$numTeams - 1 - $i];

                    if ( $t1 && $t2 ) {
                        // Alternate Home/Away based on cycle
                        $home = ( $cycle % 2 == 0 ) ? $t1 : $t2;
                        $away = ( $cycle % 2 == 0 ) ? $t2 : $t1;

                        // Schedule 3 Games (Fri, Sat, Sun or Tue, Wed, Thu)
                        // Simplified: Just 3 consecutive days
                        for ( $g = 0; $g < 3; $g++ ) {
                            $gameDate = clone $seriesDate;
                            $gameDate->add( new DateInterval( "P{$g}D" ) );

                            $schedule[] = [
                                'league_id' => $leagueId,
                                'home'      => $home,
                                'away'      => $away,
                                'date'      => $gameDate->format( 'Y-m-d' ),
                                'series_id' => $seriesCount,
                            ];
                        }
                    }
                }

                // Rotate Teams (Keep index 0 fixed, rotate the rest)
                $temp = $teams[1];
                for ( $i = 1; $i < $numTeams - 1; $i++ ) {
                    $teams[$i] = $teams[$i + 1];
                }
                $teams[$numTeams - 1] = $temp;

                // Advance Calendar (3 days for series + 1 day off or travel? Let's say 3 days)
                $date->add( new DateInterval( 'P3D' ) );
                $seriesCount++;
            }
        }

        // 4. Insert into DB (Batch Insert)
        $this->saveSchedule( $schedule );
    }

    /**
     * @param array $games
     */
    private function saveSchedule( array $games )
    {
        $sql          = "INSERT INTO games (league_id, home_team_id, away_team_id, game_date, game_number, series_id) VALUES ";
        $params       = [];
        $placeholders = [];

        $gameNum = 1; // Ideally per team, but global ID is fine for now

        // Chunking to avoid SQL limits
        $chunks = array_chunk( $games, 500 );

        foreach ( $chunks as $chunk ) {
            $placeholders = [];
            $params       = [];
            foreach ( $chunk as $game ) {
                $placeholders[] = "(?, ?, ?, ?, ?, ?)";
                $params[]       = $game['league_id'];
                $params[]       = $game['home'];
                $params[]       = $game['away'];
                $params[]       = $game['date'];
                $params[]       = $gameNum++;
                $params[]       = $game['series_id'];
            }

            $query = $sql . implode( ', ', $placeholders );
            $stmt  = $this->db->prepare( $query );
            $stmt->execute( $params );
        }
    }
}
