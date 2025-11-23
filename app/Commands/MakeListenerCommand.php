<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeListenerCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'make:listener';

    protected function configure(): void
    {
        $this
            ->setName( 'make:listener' )
            ->setDescription( 'Creates a new event listener class.' )
            ->addArgument( 'name', InputArgument::REQUIRED, 'The name of the listener (e.g., SendOrderNotification).' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $name      = $input->getArgument( 'name' );
        $className = ucfirst( $name );

        $directory = dirname( __DIR__ ) . '/Listeners';
        if ( !is_dir( $directory ) ) {
            mkdir( $directory, 0755, true );
        }
        $filepath = $directory . '/' . $className . '.php';

        if ( file_exists( $filepath ) ) {
            $output->writeln( "<error>Listener '{$className}' already exists!</error>" );
            return Command::FAILURE;
        }

        $stub = file_get_contents( dirname( __DIR__, 2 ) . '/stubs/listener.stub' );
        $stub = str_replace( '{{ classname }}', $className, $stub );

        if ( file_put_contents( $filepath, $stub ) === false ) {
            $output->writeln( "<error>Failed to create listener file.</error>" );
            return Command::FAILURE;
        }

        $output->writeln( "<info>Listener '{$className}' created successfully in app/Listeners.</info>" );
        return Command::SUCCESS;
    }
}
