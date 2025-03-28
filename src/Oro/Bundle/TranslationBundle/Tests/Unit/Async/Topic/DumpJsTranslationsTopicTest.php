<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\TranslationBundle\Async\Topic\DumpJsTranslationsTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class DumpJsTranslationsTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new DumpJsTranslationsTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'expectedBody' => [],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'invalid option' => [
                'body' => ['invalid_key' => 'invalid_value'],
                'exceptionClass' => UndefinedOptionsException::class,
                'exceptionMessage' => '/The option "invalid_key" does not exist./',
            ],
        ];
    }
}
