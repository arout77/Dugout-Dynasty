<?php

namespace App\Commands;

use Core\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouteCacheCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'route:cache';

    protected function configure(): void
    {
        $this->setName( 'route:cache' )->setDescription( 'Compile all application routes into a cached file for performance.' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $output->writeln( '<comment>Caching routes...</comment>' );

        $rootPath = dirname( __DIR__, 2 );

        // Temporarily capture the routes without dispatching
        require_once $rootPath . '/routes/web.php';
        require_once $rootPath . '/routes/api.php';

        $routes = Router::getRoutes();

        $cachePath = $rootPath . '/storage/cache/routes/routes.php';
        $content   = '<?php return ' . var_export( $routes, true ) . ';';

        if ( file_put_contents( $cachePath, $content ) === false )
        {
            $output->writeln( '<error>Failed to write route cache file.</error>' );
            return Command::FAILURE;
        }

        $output->writeln( '<info>Routes cached successfully!</info>' );
        return Command::SUCCESS;
    }
}
