<?php

namespace Oro\Bundle\DataAuditBundle\Async\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Create a chunk of audit entries for entity inverse collections relations.
 */
class AuditChangedEntitiesInverseCollectionsChunkTopic extends AbstractAuditTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.data_audit.entities_inversed_relations_changed.collections_chunk';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Create a chunk of audit entries for entity inverse collections relations';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setRequired([
                'jobId',
                'entityData',
            ])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('entityData', 'array');
    }
}
