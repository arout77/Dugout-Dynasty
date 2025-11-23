<?php
declare ( strict_types = 1 );

use Phinx\Migration\AbstractMigration;

final class CreateTeamStrategiesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table( 'team_strategies', ['id' => 'strategy_id'] );

        $table->addColumn( 'team_id', 'integer' )
              ->addColumn( 'type', 'string', ['limit' => 20, 'comment' => 'rotation, lineup_rhp, lineup_lhp, bullpen'] )
              ->addColumn( 'slot', 'integer', ['comment' => '1-9 for lineup, 1-5 for rotation'] )
              ->addColumn( 'player_id', 'integer' )
              ->addColumn( 'position', 'string', ['limit' => 10, 'null' => true, 'comment' => 'Position played in lineup (e.g. SS, DH)'] )
              ->addIndex( ['team_id', 'type', 'slot'], ['unique' => true] ) // Ensure one player per slot per type
              ->create();
    }
}
