<?php
declare ( strict_types = 1 );

use Phinx\Migration\AbstractMigration;

final class CreateGamesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table( 'games', ['id' => 'game_id'] );

        $table->addColumn( 'league_id', 'integer' )
              ->addColumn( 'home_team_id', 'integer' )
              ->addColumn( 'away_team_id', 'integer' )
              ->addColumn( 'game_date', 'date' )
              ->addColumn( 'game_number', 'integer', ['comment' => '1 to 162'] )
              ->addColumn( 'status', 'string', ['limit' => 20, 'default' => 'scheduled', 'comment' => 'scheduled, played, rainout'] )
              ->addColumn( 'home_score', 'integer', ['null' => true] )
              ->addColumn( 'away_score', 'integer', ['null' => true] )
              ->addColumn( 'series_id', 'integer', ['null' => true, 'comment' => 'Grouping ID for 3-4 game sets'] )
              ->addIndex( ['league_id'] )
              ->addIndex( ['game_date'] )
              ->addForeignKey( 'home_team_id', 'teams', 'team_id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'] )
              ->addForeignKey( 'away_team_id', 'teams', 'team_id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'] )
              ->create();
    }
}
