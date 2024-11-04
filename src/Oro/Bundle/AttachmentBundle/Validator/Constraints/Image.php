<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Image as BaseImage;

/**
 * This constraint can be used to check uploaded images.
 */
class Image extends BaseImage
{
    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_attachment_image_validator';
    }
}
