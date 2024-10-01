<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication;

/**
 * Removes request target_path_parameter parameter if its value is like route.
 */
class DefaultAuthenticationSuccessHandler extends Authentication\DefaultAuthenticationSuccessHandler
{
    use ProcessRequestParameterLikeRouteTrait;

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $this->processRequestParameter($request, $this->options['target_path_parameter']);
        return parent::onAuthenticationSuccess($request, $token);
    }
}
