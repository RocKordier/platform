<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveInvalidFieldConfigQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    const LIMIT = 100;

    #[\Override]
    public function getDescription()
    {
        return 'Removes invalid configs from field configs';
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getEntityConfigFieldsCount() / static::LIMIT);

        $entityConfigFieldQb = $this->createEntityConfigFieldQb()
            ->setMaxResults(static::LIMIT);

        for ($i = 0; $i < $steps; $i++) {
            $rows = $entityConfigFieldQb
                ->setFirstResult($i * static::LIMIT)
                ->execute()
                ->fetchAllAssociative;

            foreach ($rows as $row) {
                $this->processRow($row);
            }
        }
    }

    protected function processRow(array $row)
    {
        $convertedData = Type::getType(Types::ARRAY)
            ->convertToPHPValue($row['data'], $this->connection->getDatabasePlatform());
        if (!isset($convertedData['extend']['pending_changes'])) {
            return;
        }

        unset($convertedData['extend']['pending_changes']);
        $this->connection->update(
            'oro_entity_config_field',
            ['data' => $convertedData],
            ['id' => $row['id']],
            [Types::ARRAY]
        );
    }

    /**
     * @return int
     */
    protected function getEntityConfigFieldsCount()
    {
        return $this->createEntityConfigFieldQb()
            ->select('COUNT(1)')
            ->execute()
            ->fetchOne();
    }

    /**
     * @return QueryBuilder
     */
    protected function createEntityConfigFieldQb()
    {
        return $this->connection->createQueryBuilder()
            ->select('cf.id, cf.data')
            ->from('oro_entity_config_field', 'cf');
    }
}
