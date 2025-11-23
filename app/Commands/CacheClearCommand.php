<?php

namespace App\Commands;

use Core\Cache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClearCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'cache:clear';

    // The Cache manager is now injected via the constructor
    /**
     * @param Cache $cache
     */
    public function __construct( protected Cache $cache )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName( 'cache:clear' )->setDescription( 'Flush the application cache.' );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        // Use the injected cache instance to call the method
        $this->cache->flush();

        $output->writeln( '<info>Application cache cleared!</info>' );
        return Command::SUCCESS;
    }
}
