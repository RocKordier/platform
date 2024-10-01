<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Ensures that the data added to the search index is of the appropriate 'integer' type.
 * Allows to convert type 'boolean' to type 'integer'.
 */
class IntegerTypeCast extends AbstractTypeCastingHandler
{
    #[\Override]
    public function castValue(mixed $value): mixed
    {
        if ($this->isSupported($value)) {
            return (int)$value;
        }

        return parent::castValue($value);
    }

    #[\Override]
    public function isSupported($value): bool
    {
        return is_int($value) || is_bool($value);
    }

    #[\Override]
    public static function getType(): string
    {
        return Query::TYPE_INTEGER;
    }
}
