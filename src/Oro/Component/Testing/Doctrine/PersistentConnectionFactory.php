<?php

namespace Oro\Component\Testing\Doctrine;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;

/**
 * The factory to create a persistent connection for tests
 */
class PersistentConnectionFactory extends ConnectionFactory
{
    #[\Override]
    public function createConnection(
        array $params,
        Configuration $config = null,
        EventManager $eventManager = null,
        array $mappingTypes = array()
    ) {
        $wrapperClass = PersistentConnection::class;
        if (isset($params['wrapperClass'])) {
            if (!is_a($params['wrapperClass'], $wrapperClass, true)) {
                throw new \LogicException(sprintf(
                    'The connection wrapper class "%s" has to be "%s" or its subtype.',
                    $params['wrapperClass'],
                    $wrapperClass
                ));
            }
        } else {
            $params['wrapperClass'] = $wrapperClass;
        }

        return parent::createConnection($params, $config, $eventManager, $mappingTypes);
    }
}
