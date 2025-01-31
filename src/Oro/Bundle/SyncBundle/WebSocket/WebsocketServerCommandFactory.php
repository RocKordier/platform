<?php

namespace Oro\Bundle\SyncBundle\WebSocket;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\ServerLauncherInterface;
use Oro\Bundle\SyncBundle\Command\WebsocketServerCommand;

/**
 * Gos websocket command factory
 */
class WebsocketServerCommandFactory
{
    public function createGosWebsocketCommand(
        ServerLauncherInterface $entryPoint,
        DsnBasedParameters $dsnParameters,
        ?ServerRegistry $serverRegistry = null
    ): WebsocketServerCommand {
        return new WebsocketServerCommand(
            $entryPoint,
            $dsnParameters->getHost(),
            (int)$dsnParameters->getPort(),
            $serverRegistry
        );
    }
}
