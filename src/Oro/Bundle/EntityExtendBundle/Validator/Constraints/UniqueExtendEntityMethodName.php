<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The validation constraint to validate method name for uniqueness for field name.
 */
class UniqueExtendEntityMethodName extends Constraint
{
    /** @var string */
    public $message = 'The \'{{ value }}\' word is reserved for system purposes.';

    /** @var string */
    public $unexpectedNameMessage = 'The field name must be \'{{ field }}\'.';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
