<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Oro\Bundle\LayoutBundle\Attribute\Layout as LayoutAttribute;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\BlockViewNotFoundException;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Checks whether a web request should be processed by the layout engine
 * (the Request object has the #[Layout] attribute in the "_layout" attribute),
 * and if so, renders the layout.
 */
class LayoutListener implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            LayoutManager::class,
            LoggerInterface::class
        ];
    }

    /**
     * @throws LogicException if #[Layout] attribute is used in incorrect way
     */
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();

        /** @var LayoutAttribute|null $layoutAttribute */
        $layoutAttribute = $request->attributes->get('_layout');
        if (null === $layoutAttribute) {
            return;
        }

        if ($request->attributes->get('_template')) {
            throw new LogicException(
                'The #[Template] attribute cannot be used together with the #[Layout] attribute.'
            );
        }

        $layout = null;
        $context = null;
        $parameters = $event->getControllerResult();
        if (\is_array($parameters)) {
            $context = new LayoutContext($parameters, (array) $layoutAttribute->getVars());
        } elseif ($parameters instanceof ContextInterface) {
            $context = $parameters;
            $vars = $layoutAttribute->getVars();
            if (!empty($vars)) {
                $context->getResolver()->setRequired($vars);
            }
        } elseif ($parameters instanceof Layout) {
            if (!$layoutAttribute->isEmpty()) {
                throw new LogicException(
                    'The empty #[Layout] attribute must be used when '
                    . 'the controller returns an instance of "Oro\Component\Layout\Layout".'
                );
            }
            $layout = $parameters;
        } else {
            return;
        }

        if ($layout) {
            $response = new Response($layout->render());
        } else {
            $this->configureContext($context, $layoutAttribute);
            $layoutManager = $this->container->get(LayoutManager::class);
            $layoutManager->getLayoutBuilder()->setBlockTheme($layoutAttribute->getBlockThemes());
            $response = $this->getLayoutResponse($context, $request, $layoutManager);
        }

        $response->setStatusCode($context->getOr('response_status_code', 200));

        $event->setResponse($response);
    }

    private function configureContext(ContextInterface $context, LayoutAttribute $layoutAttribute): void
    {
        $action = $layoutAttribute->getAction();
        if ($action) {
            $currentAction = $context->getOr('action');
            if (empty($currentAction)) {
                $context->set('action', $action);
            }
        }

        $theme = $layoutAttribute->getTheme();
        if ($theme) {
            $currentTheme = $context->getOr('theme');
            if (empty($currentTheme)) {
                $context->set('theme', $theme);
            }
        }
    }

    private function getLayoutResponse(
        ContextInterface $context,
        Request $request,
        LayoutManager $layoutManager
    ): Response {
        $blockIds = $request->get('layout_block_ids');
        if ($blockIds && \is_array($blockIds)) {
            $data = [];
            foreach ($blockIds as $blockId) {
                if ($blockId) {
                    try {
                        $data[$blockId] = $layoutManager->getLayout($context, $blockId)->render();
                    } catch (BlockViewNotFoundException $e) {
                        $this->logNotFoundViewException($blockId, $e);
                    }
                }
            }

            return new JsonResponse($data);
        }

        return new Response($layoutManager->getLayout($context)->render());
    }

    /**
     * @param string $blockId
     * @param BlockViewNotFoundException $e
     */
    private function logNotFoundViewException($blockId, BlockViewNotFoundException $e): void
    {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get(LoggerInterface::class);
        $logger->warning(
            sprintf('Unknown block "%s" was requested via layout_block_ids', $blockId),
            ['exception' => $e]
        );
    }
}
