<?php

/**
 * Command: import:league-stats
 * Purpose: Imports historical league statistics for era normalization.
 * Usage: php rhapsody import:league-stats [file_path]
 */

namespace App\Commands;

use Core\Database;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportLeagueStatsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'import:league-stats';

    protected function configure()
    {
        $this->setName( 'import:league-stats' )
             ->setDescription( 'Imports historical league pitching stats from storage/pitcher_data.txt' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $file = __DIR__ . '/../../storage/pitcher_data.txt';

        if ( !file_exists( $file ) ) {
            $output->writeln( "<error>File not found: $file</error>" );
            return Command::FAILURE;
        }

        $db    = Database::getInstance();
        $lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $count = 0;

        // SQL to insert/update
        $sql = "INSERT INTO league_stats (year, avg_era, avg_whip, avg_babip, avg_h_9, avg_hr_9, avg_bb_9, avg_k_9)
                VALUES (:year, :era, :whip, :babip, :h9, :hr9, :bb9, :so9)
                ON DUPLICATE KEY UPDATE
                avg_era=VALUES(avg_era), avg_whip=VALUES(avg_whip), avg_babip=VALUES(avg_babip),
                avg_h_9=VALUES(avg_h_9), avg_hr_9=VALUES(avg_hr_9), avg_bb_9=VALUES(avg_bb_9), avg_k_9=VALUES(avg_k_9)";

        $stmt = $db->prepare( $sql );

        foreach ( $lines as $line ) {
            if ( strpos( $line, 'Year' ) !== false || strpos( $line, 'R/G' ) !== false ) {
                continue;
            }

            $cols = preg_split( '/\s+/', trim( $line ) );
            if ( count( $cols ) < 15 ) {
                continue;
            }

            // Use Reverse Mapping (counting from the end is safer for historical data)
            // Last cols: ... WHIP, BAbip, H9, HR9, BB9, SO9, SO/W, E
            // Indices:      -8    -7     -6  -5   -4   -3   -2    -1

            try {
                $len = count( $cols );

                $year = (int) $cols[0];
                $era  = (float) $cols[2]; // ERA is usually consistent at index 2

                $whip  = (float) $cols[$len - 8];
                $babip = $this->parseDecimal( $cols[$len - 7] ); // Fix .291 parsing
                $h9    = (float) $cols[$len - 6];
                $hr9   = (float) $cols[$len - 5];
                $bb9   = (float) $cols[$len - 4];
                $so9   = (float) $cols[$len - 3];

                $stmt->execute( [
                    ':year'  => $year,
                    ':era'   => $era,
                    ':whip'  => $whip,
                    ':babip' => $babip,
                    ':h9'    => $h9,
                    ':hr9'   => $hr9,
                    ':bb9'   => $bb9,
                    ':so9'   => $so9,
                ] );
                $count++;
            } catch ( \Exception $e ) {
                $output->writeln( "<error>Error importing row: " . $e->getMessage() . "</error>" );
            }
        }

        $output->writeln( "<info>Successfully imported stats for $count years.</info>" );
        return Command::SUCCESS;
    }

    /**
     * @param $val
     */
    private function parseDecimal( $val )
    {
        // Handle ".291" or "0.291" correctly
        if ( str_starts_with( $val, '.' ) ) {
            $val = '0' . $val;
        }
        return (float) $val;
    }
}
