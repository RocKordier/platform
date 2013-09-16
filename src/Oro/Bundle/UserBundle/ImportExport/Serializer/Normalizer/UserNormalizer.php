<?php

namespace Oro\Bundle\UserBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\AbstractContextModeAwareNormalizer;
use Oro\Bundle\UserBundle\Entity\User;

class UserNormalizer extends AbstractContextModeAwareNormalizer
{
    const FULL_MODE  = 'full';
    const SHORT_MODE = 'short';
    const USER_TYPE  = 'Oro\Bundle\UserBundle\Entity\User';

    public function __construct()
    {
        parent::__construct(array(self::FULL_MODE, self::SHORT_MODE));
    }

    /**
     * Short mode normalization
     *
     * @param User $object
     * @param mixed $format
     * @param array $context
     * @return array
     */
    protected function normalizeShort($object, $format = null, array $context = array())
    {
        return array(
            'firstName' => $object->getFirstname(),
            'lastName' => $object->getLastname(),
        );
    }

    /**
     * Short mode denormalization
     *
     * @param mixed $data
     * @param string $class
     * @param mixed $format
     * @param array $context
     * @return User
     */
    protected function denormalizeShort($data, $class, $format = null, array $context = array())
    {
        $result = new User();
        if (!empty($data['firstName'])) {
            $result->setFirstname($data['firstName']);
        }
        if (!empty($data['lastName'])) {
            $result->setLastname($data['lastName']);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof User;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && $type == static::USER_TYPE;
    }
}
