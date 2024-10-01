<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements
    Migration,
    NameGeneratorAwareInterface,
    ExtendExtensionAwareInterface,
    ActivityExtensionAwareInterface
{
    use ExtendNameGeneratorAwareTrait;
    use ExtendExtensionAwareTrait;
    use ActivityExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->activityExtension->addActivityAssociation($schema, 'oro_email', 'oro_user', true);

        $this->assignActivities('oro_email', 'oro_user', 'owner_user_id', $queries);
    }

    private function assignActivities(
        string $sourceTableName,
        string $targetTableName,
        string $ownerColumnName,
        QueryBag $queries
    ): void {
        // prepare select email_id:contact_id sql
        $fromAndRecipients = '
            SELECT DISTINCT email_id, owner_id FROM (
                SELECT e.id as email_id, ea.{owner} as owner_id
                FROM oro_email_address ea
                    INNER JOIN oro_email e ON e.from_email_address_id = ea.id
                WHERE ea.{owner} IS NOT NULL
                UNION
                SELECT er.email_id as email_id, ea.{owner} as owner_id
                FROM oro_email_address ea
                    INNER JOIN oro_email_recipient er ON er.email_address_id = ea.id
                WHERE ea.{owner} IS NOT NULL
            ) as subq';

        $sourceClassName   = $this->extendExtension->getEntityClassByTableName($sourceTableName);
        $targetClassName   = $this->extendExtension->getEntityClassByTableName($targetTableName);
        $fromAndRecipients = str_replace('{owner}', $ownerColumnName, $fromAndRecipients);

        $associationName = ExtendHelper::buildAssociationName(
            $targetClassName,
            ActivityScope::ASSOCIATION_KIND
        );

        $tableName = $this->nameGenerator->generateManyToManyJoinTableName(
            $sourceClassName,
            $associationName,
            $targetClassName
        );

        $queries->addQuery(sprintf("INSERT INTO %s %s", $tableName, $fromAndRecipients));
    }
}
