<?php

namespace App\Commands;

use Core\Cache;
use Core\Mailer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckVersionCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:check-version';

    // The default repository to check.
    private string $defaultRepository = 'arout77/Rhapsody-Framework';

    /**
     * @param array $config
     * @param Mailer $mailer
     * @param Cache $cache
     */
    public function __construct(
        protected array $config,
        protected Mailer $mailer,
        protected Cache $cache
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName( 'app:check-version' )
            ->setDescription( 'Checks GitHub for the latest release of a repository.' )
            ->addArgument( 'repository', InputArgument::OPTIONAL, 'The GitHub repository to check in "owner/repo" format.', $this->defaultRepository );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $repository     = $input->getArgument( 'repository' );
        $currentVersion = $this->config['app_version'];

        $output->writeln( "<comment>Current version:</comment> {$currentVersion}" );
        $output->writeln( "<comment>Checking for latest release at {$repository}...</comment>" );

        $data = $this->fetchFromApi( $repository, $output );
        if ( !$data )
        {
            $output->writeln( "<error>Could not fetch release information from GitHub. This could be due to a network issue, firewall, or an outdated SSL certificate on the server.</error>" );
            return Command::FAILURE;
        }

        // The first item in the array is always the most recent release.
        $latestRelease = $data[0] ?? null;

        if ( !$latestRelease || !isset( $latestRelease['tag_name'] ) )
        {
            $output->writeln( "<error>Could not find any releases in the API response.</error>" );
            return Command::FAILURE;
        }

        $latestVersion = $latestRelease['tag_name'];
        $output->writeln( "<comment>Latest release found:</comment> {$latestVersion}" );

        if ( version_compare( $latestVersion, $currentVersion, '>' ) )
        {
            $output->writeln( "<info>A new version ({$latestVersion}) is available!</info>" );
            $notificationCacheKey = 'notified_version_' . str_replace( '.', '_', $latestVersion );

            if ( $this->config['app_env'] === 'development' )
            {
                $output->writeln( "Development mode: Caching notification for the navbar." );
                $this->cache->put( 'update_available', $latestVersion, 1440 );
            }
            else
            {
                if ( $this->cache->has( $notificationCacheKey ) )
                {
                    $output->writeln( "<comment>An email notification for version {$latestVersion} has already been sent. Skipping.</comment>" );
                }
                else
                {
                    $output->writeln( "Production mode: Attempting to send email notification..." );
                    $wasSent = $this->sendEmailNotification( $latestVersion, $latestRelease['html_url'], $output );
                    if ( $wasSent )
                    {
                        $this->cache->put( $notificationCacheKey, true, 43200 );
                    }
                }
            }
        }
        else
        {
            $output->writeln( "Application is up to date. Clearing any update caches." );
            $this->cache->forget( 'update_available' );
        }

        return Command::SUCCESS;
    }

    /**
     * @param string $repository
     * @param OutputInterface $output
     */
    private function fetchFromApi( string $repository, OutputInterface $output ): ?array {
        // Use the /releases endpoint to get all releases, including pre-releases
        $apiUrl     = "https://api.github.com/repos/{$repository}/releases";
        $caCertPath = dirname( __DIR__, 2 ) . '/config/cacert.pem';

        if ( !file_exists( $caCertPath ) )
        {
            $output->writeln( "<error>SSL Certificate bundle not found at '{$caCertPath}'. Please download it from curl.se/docs/caextract.html</error>" );
            return null;
        }

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $apiUrl );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Rhapsody-Framework-Version-Checker' );

        // Explicitly provide the path to the certificate bundle
        curl_setopt( $ch, CURLOPT_CAINFO, $caCertPath );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );

        $response = curl_exec( $ch );
        $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        if ( curl_errno( $ch ) )
        {
            $output->writeln( "<error>cURL Error: " . curl_error( $ch ) . "</error>" );
            curl_close( $ch );
            return null;
        }

        curl_close( $ch );

        if ( $httpCode !== 200 || $response === false )
        {
            return null;
        }

        return json_decode( $response, true );
    }

    /**
     * @param string $newVersion
     * @param string $releaseUrl
     * @param OutputInterface $output
     */
    private function sendEmailNotification( string $newVersion, string $releaseUrl, OutputInterface $output ): bool
    {
        $to = $_ENV['MAIL_ADMIN_EMAIL'] ?? null;
        if ( !$to )
        {
            $output->writeln( "<error>MAIL_ADMIN_EMAIL is not set in your .env file. Cannot send notification.</error>" );
            return false;
        }
        try {
            $subject  = "New Rhapsody Version Available: {$newVersion}";
            $htmlBody = "<p>A new version of your application is available: <strong>{$newVersion}</strong>.</p>";
            $htmlBody .= "<p>View the release notes and update here: <a href='{$releaseUrl}'>{$releaseUrl}</a></p>";
            $this->mailer->send( $to, $subject, $htmlBody );
            $output->writeln( "<info>Email notification sent successfully to {$to}.</info>" );
            return true;
        }
        catch ( \Exception $e )
        {
            error_log( "CheckVersionCommand Mailer Error: " . $e->getMessage() );
            $output->writeln( "<error>Failed to send email. Check your server's error log for details.</error>" );
            return false;
        }
    }
}
