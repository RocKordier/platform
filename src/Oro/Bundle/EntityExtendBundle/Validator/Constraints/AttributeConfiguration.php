<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint class for AttributeConfigurationValidator.
 */
class AttributeConfiguration extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.entity_extend.validator.attribute_configuration.error_configuration';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return AttributeConfigurationValidator::ALIAS;
    }
}
