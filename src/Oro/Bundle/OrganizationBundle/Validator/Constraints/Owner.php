<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * The constraint that can be used to validate that the current logged in user
 * is granted to change the owner for an entity.
 *
 * @Annotation
 */
#[Attribute]
class Owner extends Constraint
{
    public $message = 'You have no access to set this value as {{ owner }}.';

    #[\Override]
    public function validatedBy(): string
    {
        return 'owner_validator';
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
