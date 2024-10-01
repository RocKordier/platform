<?php

namespace Oro\Bundle\ImapBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint validates that IMAP connection can be established
 */
class ImapConnectionConfiguration extends Constraint
{
    public string $message = 'oro.imap.validator.configuration.connection.imap';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
