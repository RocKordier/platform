<?php

namespace Oro\Bundle\ImapBundle\Form\EventListener;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Origin folder subscriber for setting origin data to folder
 */
class OriginFolderSubscriber implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT   => 'setOriginToFolders'
        ];
    }

    public function setOriginToFolders(FormEvent $event)
    {
        $data = $event->getData();
        if ($data !== null && $data instanceof UserEmailOrigin) {
            foreach ($data->getFolders() as $folder) {
                $folder->setOrigin($data);
            }
            $event->setData($data);
        }
    }
}
