<?php
declare ( strict_types = 1 );

use Phinx\Migration\AbstractMigration;

final class CreateLeagueStatsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table( 'league_stats', ['id' => 'year_id'] );

        $table->addColumn( 'year', 'integer', ['limit' => 4] )
              ->addIndex( ['year'], ['unique' => true] );

        // Pitching Context
        // Increased precision to (8,4) to prevent "Out of Range" errors
        $table->addColumn( 'avg_era', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true] )
              ->addColumn( 'avg_whip', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true] )
              ->addColumn( 'avg_babip', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true] )
              ->addColumn( 'avg_h_9', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true] )
              ->addColumn( 'avg_hr_9', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true] )
              ->addColumn( 'avg_bb_9', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true] )
              ->addColumn( 'avg_k_9', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true] );

        // Hitting Context
        $table->addColumn( 'avg_ba', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true] )
              ->addColumn( 'avg_obp', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true] )
              ->addColumn( 'avg_slg', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true] )
              ->addColumn( 'avg_ops', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true] )

              ->addColumn( 'avg_1b_pa', 'decimal', ['precision' => 8, 'scale' => 5, 'null' => true] )
              ->addColumn( 'avg_2b_pa', 'decimal', ['precision' => 8, 'scale' => 5, 'null' => true] )
              ->addColumn( 'avg_3b_pa', 'decimal', ['precision' => 8, 'scale' => 5, 'null' => true] )
              ->addColumn( 'avg_hr_pa', 'decimal', ['precision' => 8, 'scale' => 5, 'null' => true] )
              ->addColumn( 'avg_bb_pa', 'decimal', ['precision' => 8, 'scale' => 5, 'null' => true] )
              ->addColumn( 'avg_so_pa', 'decimal', ['precision' => 8, 'scale' => 5, 'null' => true] )

              ->create();
    }
}
