<?php
declare ( strict_types = 1 );

use Phinx\Migration\AbstractMigration;

final class setupDugoutDynasty extends AbstractMigration
{
    public function change(): void
    {
        // 1. Create 'leagues' table to store global settings (DH Rule, etc.)
        $leagues = $this->table( 'leagues', ['id' => 'league_id'] );
        $leagues->addColumn( 'name', 'string', ['limit' => 100, 'default' => 'Dugout Dynasty League'] )
                ->addColumn( 'is_dh_enabled', 'boolean', ['default' => false] )
                ->addColumn( 'total_teams', 'integer', ['default' => 12] )
                ->addColumn( 'current_round', 'integer', ['default' => 1] )
                ->addColumn( 'current_pick', 'integer', ['default' => 1] )
                ->addColumn( 'created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'] )
                ->create();

        // 2. Update 'teams' table for the Personality System
        $teams = $this->table( 'teams', ['id' => 'team_id'] );

        // Check if columns exist before adding (idempotency)
        if ( !$teams->hasColumn( 'spending_archetype' ) ) {
            $teams->addColumn( 'spending_archetype', 'string', [
                'limit'   => 50,
                'default' => 'Balanced',
                'comment' => 'Steinbrenner, Moneyball, or Balanced',
            ] );
        }

        if ( !$teams->hasColumn( 'scouting_trait' ) ) {
            $teams->addColumn( 'scouting_trait', 'string', [
                'limit'   => 50,
                'default' => 'Analytics',
                'comment' => 'SmallBall, Power, Defense, Analytics',
            ] );
        }

        // Link teams to a league (optional for now, but good practice)
        if ( !$teams->hasColumn( 'league_id' ) ) {
            $teams->addColumn( 'league_id', 'integer', ['null' => true, 'default' => 1] );
        }

        $teams->update();

        // 3. Update 'rosters' table for the "Babe Ruth" Rule & Salary Tracking
        $rosters = $this->table( 'rosters', ['id' => 'roster_id'] );

        if ( !$rosters->hasColumn( 'player_name' ) ) {
            $rosters->addColumn( 'player_name', 'string', ['limit' => 255] )
                    ->addIndex( ['player_name'] ); // Index for fast exclusion lookups
        }

        if ( !$rosters->hasColumn( 'salary' ) ) {
            $rosters->addColumn( 'salary', 'integer', ['default' => 0] );
        }

        if ( !$rosters->hasColumn( 'stats_source' ) ) {
            $rosters->addColumn( 'stats_source', 'string', [
                'limit'   => 20,
                'default' => 'hitters',
                'comment' => 'hitters or pitchers table',
            ] );
        }

        if ( !$rosters->hasColumn( 'position' ) ) {
            $rosters->addColumn( 'position', 'string', ['limit' => 10, 'default' => 'BENCH'] );
        }

        if ( !$rosters->hasColumn( 'season_year' ) ) {
            $rosters->addColumn( 'season_year', 'integer', ['null' => true] );
        }

        $rosters->update();
    }
}
