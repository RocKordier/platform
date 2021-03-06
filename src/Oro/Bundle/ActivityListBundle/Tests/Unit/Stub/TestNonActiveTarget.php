<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Stub;

class TestNonActiveTarget
{
    /** @var int */
    protected $id;

    /**
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
