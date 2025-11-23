<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeEventCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'make:event';

    protected function configure(): void
    {
        $this
            ->setName( 'make:event' )
            ->setDescription( 'Creates a new event class.' )
            ->addArgument( 'name', InputArgument::REQUIRED, 'The name of the event (e.g., OrderShipped).' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $name      = $input->getArgument( 'name' );
        $className = ucfirst( $name );

        $directory = dirname( __DIR__ ) . '/Events';
        if ( !is_dir( $directory ) ) {
            mkdir( $directory, 0755, true );
        }
        $filepath = $directory . '/' . $className . '.php';

        if ( file_exists( $filepath ) ) {
            $output->writeln( "<error>Event '{$className}' already exists!</error>" );
            return Command::FAILURE;
        }

        $stub = file_get_contents( dirname( __DIR__, 2 ) . '/stubs/event.stub' );
        $stub = str_replace( '{{ classname }}', $className, $stub );

        if ( file_put_contents( $filepath, $stub ) === false ) {
            $output->writeln( "<error>Failed to create event file.</error>" );
            return Command::FAILURE;
        }

        $output->writeln( "<info>Event '{$className}' created successfully in app/Events.</info>" );
        return Command::SUCCESS;
    }
}
