<?php

namespace Oro\Bundle\ApiBundle\Processor\Update;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves all changes of ORM entity to the database.
 */
class SaveEntity implements ProcessorInterface
{
    public const OPERATION_NAME = 'save_existing_entity';

    private DoctrineHelper $doctrineHelper;
    private FlushDataHandlerInterface $flushDataHandler;

    public function __construct(DoctrineHelper $doctrineHelper, FlushDataHandlerInterface $flushDataHandler)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->flushDataHandler = $flushDataHandler;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the entity was already saved
            return;
        }

        $entity = $context->getResult();
        if (!\is_object($entity)) {
            // an entity does not exist
            return;
        }

        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        $this->flushDataHandler->flushData(
            $this->doctrineHelper->getEntityManagerForClass($entityClass),
            new FlushDataHandlerContext([$context], $context->getSharedData())
        );

        $context->setProcessed(self::OPERATION_NAME);
    }
}
