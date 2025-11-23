<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class UpdateCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:update';

    /**
     * @param array $config
     */
    public function __construct( protected array $config )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName( 'app:update' )
            ->setDescription( 'Updates the application to the latest version from Git.' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $rootPath        = dirname( __DIR__, 2 );
        $maintenanceFile = $rootPath . '/storage/framework/down';
        $configPath      = $rootPath . '/config.php';

        $output->writeln( '<comment>Checking for new releases...</comment>' );
        $currentVersion    = $this->config['app_version'];
        $latestVersionData = $this->getLatestRelease( 'arout77/Rhapsody-Framework', $output );

        if ( !$latestVersionData ) {
            $output->writeln( '<error>Could not fetch release information from GitHub. Update aborted.</error>' );
            return Command::FAILURE;
        }

        $latestRelease = $latestVersionData[0] ?? null;
        if ( !$latestRelease || !isset( $latestRelease['tag_name'] ) ) {
            $output->writeln( "<error>Could not find any releases in the API response.</error>" );
            return Command::FAILURE;
        }

        $latestVersion = $latestRelease['tag_name'];
        $output->writeln( "Current version: <info>{$currentVersion}</info>" );
        $output->writeln( "Latest release: <info>{$latestVersion}</info>" );

        if ( version_compare( $latestVersion, $currentVersion, '<=' ) ) {
            $output->writeln( '<info>Application is already up to date.</info>' );
            return Command::SUCCESS;
        }

        $output->writeln( "\n<comment>New version found. Entering maintenance mode...</comment>" );
        if ( !is_dir( dirname( $maintenanceFile ) ) ) {
            mkdir( dirname( $maintenanceFile ), 0755, true );
        }
        touch( $maintenanceFile );

        $updateSucceeded = false;
        try {
            $output->writeln( '<bg=yellow;options=bold>[WARNING] This will discard any local modifications to tracked files.</>' );

            // 1. Fetch the latest changes from the remote repository.
            $this->runProcess( ['git', 'fetch'], $output, 'Fetching latest repository data...' );

            // 2. Forcefully reset the local branch to match the remote branch. This prevents all merge conflicts.
            $this->runProcess( ['git', 'reset', '--hard', 'origin/main'], $output, 'Resetting local branch to match remote...' );

            // 3. Update dependencies based on the new composer.lock file.
            $this->runProcess( ['composer', 'install', '--no-dev', '--optimize-autoloader'], $output );

            // 4. Run framework update and caching commands.
            $this->runProcess( [PHP_BINARY, 'rhapsody', 'env:sync'], $output );
            $this->runProcess( [PHP_BINARY, 'rhapsody', 'migrate'], $output );
            $this->runProcess( [PHP_BINARY, 'rhapsody', 'route:cache'], $output );
            $this->runProcess( [PHP_BINARY, 'rhapsody', 'cache:clear'], $output );

            // 5. Finally, update the version number in the now-updated config.php
            $output->writeln( '<comment>Updating version in config.php...</comment>' );
            $configFileContent    = file_get_contents( $configPath );
            $newConfigFileContent = preg_replace( "/('app_version'\s*=>\s*)'.*?'/", "$1'{$latestVersion}'", $configFileContent );
            file_put_contents( $configPath, $newConfigFileContent );

            $updateSucceeded = true;
            $output->writeln( "\n<info>Application successfully updated to version {$latestVersion}!</info>" );
            return Command::SUCCESS;

        } catch ( \Exception $e ) {
            $output->writeln( '<error>An error occurred during the update process:</error>' );
            $output->writeln( $e->getMessage() );
            $output->writeln( '<error>Update failed! The application has been left in maintenance mode for manual inspection.</error>' );
            return Command::FAILURE;
        } finally {
            if ( $updateSucceeded && file_exists( $maintenanceFile ) ) {
                $output->writeln( '<comment>Exiting maintenance mode...</comment>' );
                unlink( $maintenanceFile );
            }
        }
    }

    /**
     * @param string $repository
     * @param OutputInterface $output
     */
    private function getLatestRelease( string $repository, OutputInterface $output ): ?array
    {
        $apiUrl     = "https://api.github.com/repos/{$repository}/releases";
        $caCertPath = dirname( __DIR__, 2 ) . '/config/cacert.pem';

        if ( !file_exists( $caCertPath ) ) {
            $output->writeln( "<error>SSL Certificate bundle not found at '{$caCertPath}'.</error>" );
            return null;
        }

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $apiUrl );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Rhapsody-Framework-Updater' );
        curl_setopt( $ch, CURLOPT_CAINFO, $caCertPath );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );

        $response = curl_exec( $ch );
        $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        if ( curl_errno( $ch ) ) {
            $output->writeln( "<error>cURL Error: " . curl_error( $ch ) . "</error>" );
            curl_close( $ch );
            return null;
        }

        curl_close( $ch );

        if ( $httpCode !== 200 || $response === false ) {
            return null;
        }

        return json_decode( $response, true );
    }

    /**
     * @param array $command
     * @param OutputInterface $output
     * @param string $message
     */
    private function runProcess( array $command, OutputInterface $output, string $message = null ): void
    {
        $process = new Process( $command );
        $process->setTimeout( 300 );
        $output->writeln( "\n<info>" . ( $message ?: "Running: " . $process->getCommandLine() ) . "</info>" );

        $process->run( function ( $type, $buffer ) use ( $output ) {
            $output->write( $buffer );
        } );

        if ( !$process->isSuccessful() ) {
            throw new \RuntimeException( $process->getErrorOutput() ?: $process->getOutput() );
        }
    }
}
