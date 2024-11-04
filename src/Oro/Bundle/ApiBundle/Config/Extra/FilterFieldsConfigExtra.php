<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to request to add only specific fields to a result.
 */
class FilterFieldsConfigExtra implements ConfigExtraInterface
{
    public const NAME = 'filter_fields';

    private array $fieldFilters;

    /**
     * @param array $fieldFilters The list of fields that should be returned for a specified type of an entity.
     *                            [entity type or entity class => [field name, ...] or null, ...]
     */
    public function __construct(array $fieldFilters)
    {
        $this->fieldFilters = $fieldFilters;
    }

    /**
     * Gets the list of fields that should be returned for a specified type of an entity.
     *
     * @return array [entity type or entity class => [field name, ...] or null, ...]
     */
    public function getFieldFilters(): array
    {
        return $this->fieldFilters;
    }

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function configureContext(ConfigContext $context): void
    {
        $context->set(self::NAME, $this->fieldFilters);
    }

    #[\Override]
    public function isPropagable(): bool
    {
        return true;
    }

    #[\Override]
    public function getCacheKeyPart(): ?string
    {
        $result = '';
        foreach ($this->fieldFilters as $entity => $fields) {
            $result .= $entity;
            if (null !== $fields) {
                $result .= '(' . implode(',', $fields) . ')';
            }
        }

        return 'fields:' . $result;
    }
}
