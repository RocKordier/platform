<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Contains the properties of the constraint definition which checks uniqueness of user by email.
 */
class UniqueUserEmail extends Constraint
{
    public string $message = 'oro.user.message.user_email_exists';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
