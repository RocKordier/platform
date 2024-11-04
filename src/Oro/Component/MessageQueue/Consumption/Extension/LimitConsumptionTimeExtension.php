<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Interrupts the consumption if the limit time has passed
 */
class LimitConsumptionTimeExtension extends AbstractExtension
{
    /**
     * @var \DateTime
     */
    protected $timeLimit;

    public function __construct(\DateTime $timeLimit)
    {
        $this->timeLimit = $timeLimit;
    }

    #[\Override]
    public function onBeforeReceive(Context $context)
    {
        $this->checkTime($context);
    }

    #[\Override]
    public function onIdle(Context $context)
    {
        $this->checkTime($context);
    }

    #[\Override]
    public function onPostReceived(Context $context)
    {
        $this->checkTime($context);
    }

    protected function checkTime(Context $context)
    {
        $now = new \DateTime();
        if ($now >= $this->timeLimit) {
            $context->getLogger()->debug(sprintf(
                'Execution interrupted as limit time has passed. now: "%s", time-limit: "%s"',
                $now->format(DATE_ISO8601),
                $this->timeLimit->format(DATE_ISO8601)
            ));

            $context->setExecutionInterrupted(true);
            $context->setInterruptedReason('The limit time has passed.');
        }
    }
}
