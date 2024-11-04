<?php

namespace Oro\Bundle\ReminderBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Handles entities that implements RemindableInterface.
 */
class ReminderListener implements EventSubscriber, ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_reminder.entity.manager' => ReminderManager::class
        ];
    }

    #[\Override]
    public function getSubscribedEvents()
    {
        return [
            Events::postLoad,
            Events::postPersist
        ];
    }

    /**
     * After entity with reminders was loaded, load reminders
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof RemindableInterface) {
            $this->getReminderManager()->loadReminders($entity);
        }
    }

    /**
     * Save reminders for new entities
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();
        if ($entity instanceof RemindableInterface) {
            $this->getReminderManager()->saveReminders($entity);
        }
    }

    private function getReminderManager(): ReminderManager
    {
        return $this->container->get('oro_reminder.entity.manager');
    }
}
