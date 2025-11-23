<?php

namespace App\Commands;

use Core\Database;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportHitterStatsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'import:hitter-stats';

    protected function configure()
    {
        $this->setName( 'import:hitter-stats' )
             ->setDescription( 'Imports historical league hitter stats from storage/hitter_data.txt (CSV Format)' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return float
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $file = __DIR__ . '/../../storage/hitter_data.txt';

        if ( !file_exists( $file ) ) {
            $output->writeln( "<error>File not found: $file</error>" );
            $output->writeln( "Please paste the CSV data into storage/hitter_data.txt" );
            return Command::FAILURE;
        }

        $db    = Database::getInstance();
        $lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $count = 0;

        $sql = "INSERT INTO league_stats
                (year, avg_ba, avg_obp, avg_slg, avg_ops, avg_1b_pa, avg_2b_pa, avg_3b_pa, avg_hr_pa, avg_bb_pa, avg_so_pa)
                VALUES
                (:year, :ba, :obp, :slg, :ops, :r1b, :r2b, :r3b, :rhr, :rbb, :rso)
                ON DUPLICATE KEY UPDATE
                avg_ba=VALUES(avg_ba), avg_obp=VALUES(avg_obp), avg_slg=VALUES(avg_slg), avg_ops=VALUES(avg_ops),
                avg_1b_pa=VALUES(avg_1b_pa), avg_2b_pa=VALUES(avg_2b_pa), avg_3b_pa=VALUES(avg_3b_pa),
                avg_hr_pa=VALUES(avg_hr_pa), avg_bb_pa=VALUES(avg_bb_pa), avg_so_pa=VALUES(avg_so_pa)";

        $stmt = $db->prepare( $sql );

        foreach ( $lines as $line ) {
            // Skip header row
            if ( strpos( $line, 'Year,' ) === 0 || strpos( $line, 'Tms,' ) !== false ) {
                continue;
            }

            // Parse CSV line
            $cols = str_getcsv( $line );

            // Ensure we have enough columns (CSV should have ~30 cols)
            if ( count( $cols ) < 20 ) {
                $output->writeln( "<comment>Skipping malformed line: $line</comment>" );
                continue;
            }

            // CSV Column Mapping (Based on your provided data):
            // 0:Year, 6:PA, 10:1B, 11:2B, 12:3B, 13:HR, 17:BB, 18:SO, 19:BA, 20:OBP, 21:SLG, 22:OPS

            $year = (int) $cols[0];
            $pa   = (float) $cols[6];

            if ( $pa == 0 ) {
                continue;
            }

            try {
                // Helper to safely parse floats like ".245"
                $parse = function ( $val ) {
                    if ( $val === '' || $val === null ) {
                        return 0.0;
                    }

                    if ( str_starts_with( $val, '.' ) ) {
                        return (float) ( '0' . $val );
                    }

                    return (float) $val;
                };

                $stmt->execute( [
                    ':year' => $year,
                    ':ba'   => $parse( $cols[19] ),
                    ':obp'  => $parse( $cols[20] ),
                    ':slg'  => $parse( $cols[21] ),
                    ':ops'  => $parse( $cols[22] ),

                    // Calculated Rates (Stat / PA)
                    ':r1b' => (float) $cols[10] / $pa,
                    ':r2b' => (float) $cols[11] / $pa,
                    ':r3b' => (float) $cols[12] / $pa,
                    ':rhr' => (float) $cols[13] / $pa,
                    ':rbb' => (float) $cols[17] / $pa,
                    ':rso' => (float) $cols[18] / $pa,
                ] );
                $count++;
            } catch ( \Exception $e ) {
                $output->writeln( "<error>Error importing year $year: " . $e->getMessage() . "</error>" );
            }
        }

        $output->writeln( "<info>Successfully imported Hitter stats for $count years.</info>" );
        return Command::SUCCESS;
    }
}
