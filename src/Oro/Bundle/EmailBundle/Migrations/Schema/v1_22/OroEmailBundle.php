<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_email_folder');
        if (!$table->hasColumn('sync_start_date')) {
            $table->addColumn('sync_start_date', 'datetime', ['notnull' => false]);
        }

        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_email_folder SET sync_start_date = synchronized'
        ));
    }
}
