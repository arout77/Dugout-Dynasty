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
        $stmt = $this->db->prepare( "SELECT team_id FROM teams WHERE league_id = :id" );
        $stmt->execute( [':id' => $leagueId] );
        $teams = $stmt->fetchAll( \PDO::FETCH_COLUMN );

        if ( count( $teams ) < 2 ) {
            return;
        }

        $year = date( 'Y' );
        $date = new DateTime( "$year-03-31" );
        while ( $date->format( 'D' ) != 'Thu' ) {
            $date->sub( new DateInterval( 'P1D' ) );
        }

        $schedule = [];
        $numTeams = count( $teams );

        if ( $numTeams % 2 != 0 ) {
            $teams[] = null;
            $numTeams++;
        }

        $weeksPlayed  = 0;
        $targetSeries = 54; // 54 * 3 = 162 games

        for ( $cycle = 0; $cycle < 6; $cycle++ ) {
            for ( $round = 0; $round < $numTeams - 1; $round++ ) {
                if ( $weeksPlayed >= $targetSeries ) {
                    break 2;
                }

                $seriesDate = clone $date;

                for ( $i = 0; $i < $numTeams / 2; $i++ ) {
                    $t1 = $teams[$i];
                    $t2 = $teams[$numTeams - 1 - $i];

                    if ( $t1 && $t2 ) {
                        $home = ( $cycle % 2 == 0 ) ? $t1 : $t2;
                        $away = ( $cycle % 2 == 0 ) ? $t2 : $t1;

                        for ( $g = 0; $g < 3; $g++ ) {
                            $gameDate = clone $seriesDate;
                            $gameDate->add( new DateInterval( "P{$g}D" ) );

                            // Week 0 = Games 1,2,3
                            // Week 1 = Games 4,5,6
                            $gameNum = ( $weeksPlayed * 3 ) + $g + 1;

                            $schedule[] = [
                                'league_id'   => $leagueId,
                                'home'        => $home,
                                'away'        => $away,
                                'date'        => $gameDate->format( 'Y-m-d' ),
                                'game_number' => $gameNum,
                                'series_id'   => $weeksPlayed,
                            ];
                        }
                    }
                }

                $temp = $teams[1];
                for ( $i = 1; $i < $numTeams - 1; $i++ ) {
                    $teams[$i] = $teams[$i + 1];
                }
                $teams[$numTeams - 1] = $temp;

                $date->add( new DateInterval( 'P3D' ) );
                $weeksPlayed++;
            }
        }

        $this->saveSchedule( $schedule );
    }

    /**
     * @param array $games
     */
    private function saveSchedule( array $games )
    {
        $sql = "INSERT INTO games (league_id, home_team_id, away_team_id, game_date, game_number, series_id) VALUES ";

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
                // FORCE USE OF ARRAY VALUE
                $params[] = $game['game_number'];
                $params[] = $game['series_id'];
            }

            $query = $sql . implode( ', ', $placeholders );
            $stmt  = $this->db->prepare( $query );
            $stmt->execute( $params );
        }
    }
}
