<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Fixtures;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompilerPass3 implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container)
    {
    }
}
