<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Testwork\EventDispatcher\Event\BeforeSuiteTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\SuiteAwareInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SuiteAwareSubscriber implements EventSubscriberInterface
{
    /** @var  SuiteAwareInterface[] */
    protected $services;

    /**
     * @param SuiteAwareInterface[] $services
     */
    public function __construct(array $services)
    {
        $this->services = $services;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeSuiteTested::BEFORE => ['injectSuite', 5],
        ];
    }

    public function injectSuite(BeforeSuiteTested $event)
    {
        foreach ($this->services as $service) {
            $service->setSuite($event->getSuite());
        }
    }
}
