<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine\Orm;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql;

class PdoPgsqlTest extends AbstractPdoTest
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new PdoPgsql();
    }
}
