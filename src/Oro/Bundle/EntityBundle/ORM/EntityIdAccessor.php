<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Oro\Bundle\UIBundle\Provider\ObjectIdAccessorInterface;

class EntityIdAccessor implements ObjectIdAccessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function getIdentifier($object)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($object);
    }
}
