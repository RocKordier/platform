<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * When applied to an config item of ConfigType form, this constraint allows
 * to checks whether the value is not lower then the original one.
 */
class NotLessThanOriginalValue extends Constraint
{
    /** @var string */
    public $message = 'oro.entity_extend.validator.not_less_than_original';

    /** @var string */
    public $scope;

    /** @var string */
    public $option;

    #[\Override]
    public function getTargets(): string|array
    {
        return static::PROPERTY_CONSTRAINT;
    }

    #[\Override]
    public function getRequiredOptions(): array
    {
        return ['scope', 'option'];
    }
}
