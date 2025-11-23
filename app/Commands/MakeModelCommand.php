<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModelCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'make:model';

    protected function configure(): void
    {
        $this
            ->setName( 'make:model' )
            ->setDescription( 'Creates a new model class.' )
            ->setHelp( 'This command allows you to generate a new Eloquent model file.' )
            ->addArgument( 'name', InputArgument::REQUIRED, 'The name of the model (e.g., Post).' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $name = $input->getArgument( 'name' );
        // It's standard practice for models to be singular (e.g., User, Post)
        $modelName = ucfirst( $name );

        $directory = dirname( __DIR__ ) . '/Models';
        $filepath  = $directory . '/' . $modelName . '.php';

        if ( file_exists( $filepath ) )
        {
            $output->writeln( "<error>Model '{$modelName}' already exists!</error>" );
            return Command::FAILURE;
        }

        $stub = file_get_contents( dirname( __DIR__, 2 ) . '/stubs/model.stub' );
        $stub = str_replace( '{{ classname }}', $modelName, $stub );

        if ( file_put_contents( $filepath, $stub ) === false )
        {
            $output->writeln( "<error>Failed to create model file.</error>" );
            return Command::FAILURE;
        }

        $output->writeln( "<info>Model '{$modelName}' created successfully in app/Models.</info>" );
        return Command::SUCCESS;
    }
}
