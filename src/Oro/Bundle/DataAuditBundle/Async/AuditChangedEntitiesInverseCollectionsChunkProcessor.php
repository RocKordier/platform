<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsChunkTopic;
use Oro\Bundle\DataAuditBundle\Exception\WrongDataAuditEntryStateException;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Processed chunks of collection.
 */
class AuditChangedEntitiesInverseCollectionsChunkProcessor extends AbstractAuditProcessor implements
    TopicSubscriberInterface
{
    use LoggerAwareTrait;

    private EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter;
    private JobRunner $jobRunner;
    private AuditConfigProvider $auditConfigProvider;

    public function __construct(
        EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter,
        JobRunner $jobRunner,
        AuditConfigProvider $auditConfigProvider
    ) {
        $this->entityChangesToAuditEntryConverter = $entityChangesToAuditEntryConverter;
        $this->jobRunner = $jobRunner;
        $this->auditConfigProvider = $auditConfigProvider;
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        try {
            return $this->runDelayed($message->getBody()) ? self::ACK : self::REJECT;
        } catch (JobRedeliveryException $e) {
            return self::REQUEUE;
        }
    }

    /**
     * @param array $body
     *
     * @return mixed
     */
    private function runDelayed(array $body)
    {
        return $this->jobRunner->runDelayed(
            $body['jobId'],
            function (JobRunner $jobRunner, Job $job) use ($body) {
                $map = [];
                $sourceEntityData = $body['entityData'];
                $set = $sourceEntityData['set'];
                $idx = $set === 'deleted' ? 0 : 1;

                $entityClass = $sourceEntityData['entity_class'];
                $entityId = $sourceEntityData['entity_id'];
                $entityChangeSet = $sourceEntityData['change_set'];
                $sourceKey = $entityClass . $entityId;

                foreach ($sourceEntityData['fields'] as $relatedField => $fieldData) {
                    if (!is_a($entityClass, AbstractLocalizedFallbackValue::class, true)
                        && !$this->auditConfigProvider->isPropagateField($entityClass, $relatedField)) {
                        // If its dynamic relationship between LocalizedFallbackValue, keep ordinary behaviour.
                        continue;
                    }

                    $fieldName = $fieldData['field_name'];
                    foreach ($fieldData['entity_ids'] as $id) {
                        $key = $fieldData['entity_class'] . $id;
                        $map[$key] = [
                            'entity_id' => $id,
                            'entity_class' => $fieldData['entity_class'],
                        ];
                        $map[$key]['change_set'][$fieldName] = [
                            0 => ['deleted' => []],
                            1 => ['inserted' => [], 'changed' => []]
                        ];
                        $map[$key]['change_set'][$fieldName][$idx][$set][$sourceKey] = [
                            'entity_class' => $entityClass,
                            'entity_id' => $entityId,
                            'change_set' => $entityChangeSet,
                        ];
                    }
                }

                try {
                    $this->convert($body, $map);
                } catch (WrongDataAuditEntryStateException $e) {
                    $this->logger?->warning(
                        'Unexpected retryable database exception occurred during Audit Changed Entities build.',
                        [
                            'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                            'exception' => $e
                        ]
                    );

                    throw JobRedeliveryException::create();
                } catch (\Throwable $e) {
                    $this->logger?->error(
                        'Unexpected exception occurred during Audit Changed Entities build.',
                        [
                            'topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                            'exception' => $e
                        ]
                    );
                    return false;
                }

                return true;
            }
        );
    }

    private function convert(array $body, array $map): void
    {
        $this->entityChangesToAuditEntryConverter->convert(
            $map,
            $this->getTransactionId($body),
            $this->getLoggedAt($body),
            $this->getUserReference($body),
            $this->getOrganizationReference($body),
            $this->getImpersonationReference($body),
            $this->getOwnerDescription($body)
        );
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [AuditChangedEntitiesInverseCollectionsChunkTopic::getName()];
    }
}
