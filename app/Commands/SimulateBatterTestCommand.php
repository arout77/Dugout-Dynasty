<?php

/**
 * Command: sim:batter-test
 * Purpose: Runs a simulation test for a specific batter.
 * Usage: php rhapsody sim:batter-test [batter_id]
 */

namespace App\Commands;

use App\Models\Hitter;
use App\Models\Pitcher;
use App\Services\SimulationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SimulateBatterTestCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'test:sim-player';

    protected function configure()
    {
        $this->setName( 'test:sim-player' )
             ->setDescription( 'Simulates a specific hitter season against a control group of pitchers.' )
             ->addArgument( 'name', InputArgument::REQUIRED, 'Name of the hitter (e.g. "Ruth, Babe")' )
             ->addArgument( 'year', InputArgument::OPTIONAL, 'Season Year (e.g. 1927)' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $simService   = new SimulationService();
        $hitterModel  = new Hitter();
        $pitcherModel = new Pitcher();

        $name = $input->getArgument( 'name' );
        $year = $input->getArgument( 'year' );

        // 1. Find Hitter
        $hitter = $this->findPlayer( $hitterModel, $name, $year );

        if ( !$hitter ) {
            $output->writeln( "<error>Hitter not found: $name ($year)</error>" );
            return Command::FAILURE;
        }

        $hName = $hitter['NAME'] ?? $hitter['Name'] ?? $name;
        $hYear = $hitter['YR'] ?? $hitter['Year'] ?? '????';
        $output->writeln( "<info>Simulating season for: $hName ($hYear)</info>" );

        // 2. Setup Control Group Pitchers
        // We use specific historical benchmarks to test range
        $pitchers = [
            ['name' => 'Gooden, Dwight', 'year' => 1985, 'pa' => 220], // Elite Power Pitcher
            ['name' => 'Perry, Jim', 'year' => 1970, 'pa' => 215], // Solid Starter
            ['name' => 'Turley, Bob', 'year' => 1956, 'pa' => 215], // Mediocre Starter
        ];

        $stats = [
            'PA' => 0, 'AB' => 0, 'H' => 0, '1B' => 0, '2B' => 0, '3B' => 0, 'HR' => 0, 'BB' => 0, 'SO' => 0,
        ];

        // 3. Run Simulation Loop
        foreach ( $pitchers as $pData ) {
            $pitcher = $this->findPlayer( $pitcherModel, $pData['name'], $pData['year'] );

            if ( !$pitcher ) {
                $output->writeln( "<comment>Warning: Could not find control pitcher {$pData['name']}</comment>" );
                continue;
            }

            $count = $pData['pa'];
            $output->writeln( "  vs {$pitcher['NAME']} ({$pitcher['YR']}): $count PAs" );

            for ( $i = 0; $i < $count; $i++ ) {
                $result = $simService->simulateAtBat( $hitter, $pitcher );
                $event  = $result['event'];

                $stats['PA']++;

                if ( $event === 'BB' ) {
                    $stats['BB']++;
                } elseif ( $event !== 'BB' && $event !== 'HBP' ) {
                    $stats['AB']++;
                }

                if ( in_array( $event, ['1B', '2B', '3B', 'HR'] ) ) {
                    $stats['H']++;
                    $stats[$event]++;
                } elseif ( $event === 'SO' ) {
                    $stats['SO']++;
                }
            }
        }

        // 4. Calculate & Display Rates
        $ba  = $stats['AB'] > 0 ? $stats['H'] / $stats['AB'] : 0;
        $obp = $stats['PA'] > 0 ? ( $stats['H'] + $stats['BB'] ) / $stats['PA'] : 0;
        $tb  = ( $stats['1B'] ) + ( $stats['2B'] * 2 ) + ( $stats['3B'] * 3 ) + ( $stats['HR'] * 4 );
        $slg = $stats['AB'] > 0 ? $tb / $stats['AB'] : 0;
        $ops = $obp + $slg;

        $output->writeln( "\n<comment>--- Simulation Results ---</comment>" );
        $output->writeln( "PA:  " . str_pad( $stats['PA'], 5 ) . " | AB:  " . $stats['AB'] );
        $output->writeln( "H:   " . str_pad( $stats['H'], 5 ) . " | HR:  " . $stats['HR'] );
        $output->writeln( "BB:  " . str_pad( $stats['BB'], 5 ) . " | SO:  " . $stats['SO'] );
        $output->writeln( "2B:  " . str_pad( $stats['2B'], 5 ) . " | 3B:  " . $stats['3B'] );
        $output->writeln( "------------------------" );
        $output->writeln( "BA:  " . number_format( $ba, 3 ) );
        $output->writeln( "OBP: " . number_format( $obp, 3 ) );
        $output->writeln( "SLG: " . number_format( $slg, 3 ) );
        $output->writeln( "OPS: " . number_format( $ops, 3 ) );

        return Command::SUCCESS;
    }

    /**
     * @param $model
     * @param $name
     * @param $year
     * @return mixed
     */
    private function findPlayer( $model, $name, $year )
    {
        $db    = $model->getDb();
        $table = ( $model instanceof Hitter ) ? 'hitters' : 'pitchers';

        $sql    = "SELECT * FROM $table WHERE NAME LIKE :name";
        $params = [':name' => "%$name%"];

        if ( $year ) {
            $sql .= " AND YR = :year";
            $params[':year'] = $year;
        }

        $sql .= " LIMIT 1";

        $stmt = $db->prepare( $sql );
        $stmt->execute( $params );
        return $stmt->fetch( \PDO::FETCH_ASSOC );
    }
}
