<?php

namespace App\Commands;

use App\Models\Team;
use App\Services\ScheduleService;
use Core\Database;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixScheduleCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'fix:schedule';

    protected function configure()
    {
        $this->setName( 'fix:schedule' )
             ->setDescription( 'Wipes the games table and regenerates a clean 162-game schedule.' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $db              = Database::getInstance();
        $scheduleService = new ScheduleService();

        $output->writeln( "<info>Cleaning up duplicate games...</info>" );

        // 1. Clear existing games
        $db->exec( "TRUNCATE TABLE games" );

        // 2. Get League ID (Assuming 1 for MVP)
        $leagueId = 1;

        // 3. Verify Teams exist
        $teamModel = new Team();
        $teams     = $teamModel->getAllByDraftOrder();

        if ( count( $teams ) < 2 ) {
            $output->writeln( "<error>Not enough teams found to generate schedule.</error>" );
            return Command::FAILURE;
        }

        $output->writeln( "Found " . count( $teams ) . " teams. Generating schedule..." );

        // 4. Generate Fresh Schedule
        $scheduleService->generateSchedule( $leagueId );

        $output->writeln( "<info>Success! New 162-game schedule generated.</info>" );
        return Command::SUCCESS;
    }
}
