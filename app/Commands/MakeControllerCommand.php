<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeControllerCommand extends Command
{
    // The static property is good practice.
    /**
     * @var string
     */
    protected static $defaultName = 'make:controller';

    protected function configure(): void
    {
        $this
            ->setName( 'make:controller' )
            ->setDescription( 'Creates a new controller class.' )
            ->setHelp( 'This command allows you to generate a new controller file.' )
            ->addArgument( 'name', InputArgument::REQUIRED, 'The name of the controller.' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $name           = $input->getArgument( 'name' );
        $controllerName = str_ends_with( $name, 'Controller' ) ? $name : $name . 'Controller';

        $directory = dirname( __DIR__ ) . '/Controllers';
        $filepath  = $directory . '/' . $controllerName . '.php';

        if ( file_exists( $filepath ) )
        {
            $output->writeln( "<error>Controller '{$controllerName}' already exists!</error>" );
            return Command::FAILURE;
        }

        $stub = file_get_contents( dirname( __DIR__, 2 ) . '/stubs/controller.stub' );
        $stub = str_replace( '{{ classname }}', $controllerName, $stub );

        if ( file_put_contents( $filepath, $stub ) === false )
        {
            $output->writeln( "<error>Failed to create controller file.</error>" );
            return Command::FAILURE;
        }

        $output->writeln( "<info>Controller '{$controllerName}' created successfully.</info>" );
        return Command::SUCCESS;
    }
}
