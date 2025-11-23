<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnvSyncCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'env:sync';

    protected function configure(): void
    {
        $this
            ->setName( 'env:sync' )
            ->setDescription( 'Syncs the .env file with the latest variables from .env.example.' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $rootPath    = dirname( __DIR__, 2 );
        $envPath     = $rootPath . '/.env';
        $examplePath = $rootPath . '/.env.example';

        if ( !file_exists( $examplePath ) )
        {
            $output->writeln( '<error>.env.example file not found!</error>' );
            return Command::FAILURE;
        }

        if ( !file_exists( $envPath ) )
        {
            $output->writeln( '<comment>No .env file found. Copying .env.example...</comment>' );
            copy( $examplePath, $envPath );
            $output->writeln( '<info>.env file created successfully. Please configure it now.</info>' );
            return Command::SUCCESS;
        }

        // Read keys from both files
        $exampleKeys = $this->getKeysFromFile( $examplePath );
        $envKeys     = $this->getKeysFromFile( $envPath );

        $missingKeys = array_diff_key( $exampleKeys, $envKeys );

        if ( empty( $missingKeys ) )
        {
            $output->writeln( '<info>.env file is already up to date. Nothing to sync.</info>' );
            return Command::SUCCESS;
        }

        $output->writeln( '<comment>The following variables are missing from your .env file and will be added:</comment>' );
        $contentToAppend = "\n";

        foreach ( $missingKeys as $key => $value )
        {
            $output->writeln( " - <options=bold>{$key}</>" );
            $contentToAppend .= "{$key}={$value}\n";
        }

        // Append the missing keys to the end of the .env file
        file_put_contents( $envPath, $contentToAppend, FILE_APPEND );

        $output->writeln( "\n<info>Successfully synced .env file. Please review and set the new values.</info>" );
        return Command::SUCCESS;
    }

    /**
     * Parses a .env file and returns an associative array of keys and values.
     */
    private function getKeysFromFile( string $path ): array {
        $lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $keys  = [];
        foreach ( $lines as $line )
        {
            // Ignore comments
            if ( str_starts_with( trim( $line ), '#' ) )
            {
                continue;
            }
            if ( str_contains( $line, '=' ) )
            {
                [$key, $value]    = explode( '=', $line, 2 );
                $keys[trim( $key )] = trim( $value );
            }
        }
        return $keys;
    }
}
