<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Update\PersistEntity;
use Oro\Bundle\ApiBundle\Processor\Update\SaveEntity;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class PersistEntityTest extends UpdateProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PersistEntity */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new PersistEntity($this->doctrineHelper);
    }

    public function testProcessWhenEntityAlreadySaved(): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setProcessed(SaveEntity::OPERATION_NAME);
        $this->context->setResult(new \stdClass());
        $this->context->setMetadata($this->createMock(EntityMetadata::class));
        $this->processor->process($this->context);
    }

    public function testProcessForExistingEntity(): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setResult(new \stdClass());
        $this->context->setMetadata($this->createMock(EntityMetadata::class));
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessWhenNoEntity(): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setExisting(false);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForNotSupportedNewEntity(): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setExisting(false);
        $this->context->setResult([]);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForNotManageableNewEntity(): void
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn(null);

        $this->context->setExisting(false);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForManageableNewEntityButNoApiMetadata(): void
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);

        $em->expects(self::never())
            ->method('persist');

        $this->context->setExisting(false);
        $this->context->setResult($entity);
        $this->context->setMetadata(null);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForManageableNewEntity(): void
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entity));

        $this->context->setExisting(false);
        $this->context->setResult($entity);
        $this->context->setMetadata($this->createMock(EntityMetadata::class));
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }
}
