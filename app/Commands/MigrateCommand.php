<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MigrateCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'migrate';

    protected function configure(): void
    {
        $this->setName( 'migrate' )->setDescription( 'Runs all pending database migrations.' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $phinxPath = 'vendor/bin/phinx';
        $process   = new Process( [PHP_BINARY, $phinxPath, 'migrate'] );
        $process->run( function ( $type, $buffer ) use ( $output )
        {
            $output->write( $buffer );
        } );

        if ( !$process->isSuccessful() )
        {
            $output->writeln( '<error>Migration failed.</error>' );
            return Command::FAILURE;
        }

        $output->writeln( '<info>Migrations completed successfully.</info>' );
        return Command::SUCCESS;
    }
}
