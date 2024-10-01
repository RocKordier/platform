<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Command;

use Oro\Bundle\EntityConfigBundle\Config\ConfigCacheWarmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Warms up the entity config cache.
 */
class CacheWarmupCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-config:cache:warmup';

    private ConfigCacheWarmer $configCacheWarmer;

    public function __construct(ConfigCacheWarmer $configCacheWarmer)
    {
        $this->configCacheWarmer = $configCacheWarmer;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    public function configure()
    {
        $this
            ->setDescription('Warms up the entity config cache.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command warms up the entity config cache.

  <info>php %command.full_name%</info>

HELP
            )

        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Warm up the entity config cache');

        $this->configCacheWarmer->warmUpCache();

        return Command::SUCCESS;
    }
}
