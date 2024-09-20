<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\EventTriggerExtensionInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Collects event triggers when any entity is created, updated or removed.
 */
class EventTriggerCollectorListener implements OptionalListenerInterface, ResetInterface
{
    use OptionalListenerTrait;

    /** @var bool */
    private $forceQueued = false;

    /** @var EventTriggerExtensionInterface[] */
    private $initializedExtensions;

    /**
     * @param iterable|EventTriggerExtensionInterface[] $extensions
     */
    public function __construct(private iterable $extensions)
    {
    }

    public function setForceQueued(bool $forceQueued = false): void
    {
        $this->forceQueued = $forceQueued;
        $this->reset();
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->initializedExtensions = null;
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->schedule($args->getObject(), EventTriggerInterface::EVENT_CREATE);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $changeSet = $args->getEntityChangeSet();
        $fields = array_keys($changeSet);
        foreach ($fields as $field) {
            $changeSet[$field] = ['old' => $args->getOldValue($field), 'new' => $args->getNewValue($field)];
        }

        $this->schedule($args->getObject(), EventTriggerInterface::EVENT_UPDATE, $changeSet);
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->schedule($args->getObject(), EventTriggerInterface::EVENT_DELETE);
    }

    public function onClear(OnClearEventArgs $args): void
    {
        $entityClass = $args->clearsAllEntities() ? null : $args->getEntityClass();
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            $extension->clear($entityClass);
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            $extension->process($args->getObjectManager());
        }
    }

    /**
     * @param object $entity
     * @param string $event
     * @param array|null $changeSet
     */
    private function schedule($entity, $event, array $changeSet = null): void
    {
        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension->hasTriggers($entity, $event)) {
                $extension->schedule($entity, $event, $changeSet);
            }
        }
    }

    /**
     * @return EventTriggerExtensionInterface[]
     */
    private function getExtensions(): array
    {
        if (null === $this->initializedExtensions) {
            $this->initializedExtensions = [];
            foreach ($this->extensions as $extension) {
                $extension->setForceQueued($this->forceQueued);
                $this->initializedExtensions[] = $extension;
            }
        }

        return $this->initializedExtensions;
    }
}
