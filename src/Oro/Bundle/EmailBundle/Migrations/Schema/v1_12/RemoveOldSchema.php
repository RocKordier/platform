<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveOldSchema implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 2;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $emailBodyTable = $schema->getTable('oro_email_body');
        if ($emailBodyTable->hasForeignKey('fk_oro_email_body_email_id')) {
            $emailBodyTable->removeForeignKey('fk_oro_email_body_email_id');
        }
        if ($emailBodyTable->hasIndex('IDX_C7CE120DA832C1C9')) {
            $emailBodyTable->dropIndex('IDX_C7CE120DA832C1C9');
        }
        if ($emailBodyTable->hasColumn('email_id')) {
            $emailBodyTable->dropColumn('email_id');
        }

        $emailUserTable = $schema->getTable('oro_email_user');
        if ($emailUserTable->hasForeignKey('FK_91F5CFF6162CB942')) {
            $emailUserTable->removeForeignKey('FK_91F5CFF6162CB942');
        }
        if ($emailUserTable->hasForeignKey('FK_91F5CFF6A832C1C9')) {
            $emailUserTable->removeForeignKey('FK_91F5CFF6A832C1C9');
        }
    }
}
