<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Stub;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;

class TestActivityList extends ActivityList
{
    /**
     * @param int $id
     */
    #[\Override]
    public function setId($id)
    {
        $this->id = $id;
    }
}
