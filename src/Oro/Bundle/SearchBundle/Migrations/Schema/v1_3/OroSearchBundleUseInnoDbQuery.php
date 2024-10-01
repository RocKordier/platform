<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_3;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class OroSearchBundleUseInnoDbQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    #[\Override]
    public function getDescription()
    {
        return 'Use InnoDB for MySQL >= 5.6';
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $version = $this->connection->fetchOne('select version()');
        if (version_compare($version, '5.6.0', '>=')) {
            $query = sprintf('ALTER TABLE `%s` ENGINE = INNODB;', $this->getTableName());
            $logger->info($query);
            $this->connection->executeQuery($query);
        }
    }

    protected function getTableName(): string
    {
        return 'oro_search_index_text';
    }
}
