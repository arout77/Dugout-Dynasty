<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouteClearCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'route:clear';

    protected function configure(): void
    {
        $this->setName( 'route:clear' )->setDescription( 'Remove the route cache file.' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $cachePath = dirname( __DIR__, 2 ) . '/storage/cache/routes/routes.php';

        if ( file_exists( $cachePath ) )
        {
            unlink( $cachePath );
            $output->writeln( '<info>Route cache cleared!</info>' );
        }
        else
        {
            $output->writeln( '<comment>Route cache was already empty.</comment>' );
        }

        return Command::SUCCESS;
    }
}
