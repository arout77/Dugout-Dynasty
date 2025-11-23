<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MakeMigrationCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'make:migration';

    protected function configure(): void
    {
        $this
            ->setName( 'make:migration' )
            ->setDescription( 'Creates a new database migration file.' )
            ->addArgument( 'name', InputArgument::REQUIRED, 'The name of the migration (e.g., CreatePostsTable).' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $name      = $input->getArgument( 'name' );
        $phinxPath = 'vendor/bin/phinx';

        $process = new Process( [PHP_BINARY, $phinxPath, 'create', $name] );
        $process->run();

        if ( !$process->isSuccessful() )
        {
            $output->writeln( '<error>Failed to create migration.</error>' );
            $output->writeln( $process->getErrorOutput() );
            return Command::FAILURE;
        }

        $output->writeln( '<info>Migration created successfully.</info>' );
        $output->writeln( $process->getOutput() );
        return Command::SUCCESS;
    }
}
