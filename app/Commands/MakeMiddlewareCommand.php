<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMiddlewareCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'make:middleware';

    protected function configure(): void
    {
        $this
            ->setName( 'make:middleware' )
            ->setDescription( 'Creates a new middleware class.' )
            ->addArgument( 'name', InputArgument::REQUIRED, 'The name of the middleware (e.g., CheckAdminRole).' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $name           = $input->getArgument( 'name' );
        $middlewareName = str_ends_with( $name, 'Middleware' ) ? $name : $name . 'Middleware';

        $directory = dirname( __DIR__ ) . '/Middleware';
        $filepath  = $directory . '/' . $middlewareName . '.php';

        if ( file_exists( $filepath ) )
        {
            $output->writeln( "<error>Middleware '{$middlewareName}' already exists!</error>" );
            return Command::FAILURE;
        }

        $stub = file_get_contents( dirname( __DIR__, 2 ) . '/stubs/middleware.stub' );
        $stub = str_replace( '{{ classname }}', $middlewareName, $stub );

        if ( file_put_contents( $filepath, $stub ) === false )
        {
            $output->writeln( "<error>Failed to create middleware file.</error>" );
            return Command::FAILURE;
        }

        $output->writeln( "<info>Middleware '{$middlewareName}' created successfully in app/Middleware.</info>" );
        return Command::SUCCESS;
    }
}
