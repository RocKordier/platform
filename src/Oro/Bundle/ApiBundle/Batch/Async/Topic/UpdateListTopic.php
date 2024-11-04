<?php

namespace Oro\Bundle\ApiBundle\Batch\Async\Topic;

use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to split data of API batch update request to chunks.
 */
class UpdateListTopic extends AbstractUpdateListTopic implements JobAwareTopicInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.api.update_list';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Splits data of API batch update request to chunks.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setRequired('fileName')
            ->setAllowedTypes('fileName', 'string');

        $resolver
            ->setRequired('chunkSize')
            ->setAllowedTypes('chunkSize', 'int');

        $resolver
            ->setRequired('includedDataChunkSize')
            ->setAllowedTypes('includedDataChunkSize', 'int');

        $resolver
            ->setDefined('splitterState')
            ->setAllowedTypes('splitterState', 'array');

        $resolver
            ->setDefined('aggregateTime')
            ->setDefault('aggregateTime', 0)
            ->setAllowedTypes('aggregateTime', 'int');
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        return sprintf('oro:batch_api:%d', $messageBody['operationId']);
    }
}
