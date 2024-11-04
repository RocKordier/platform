<?php

namespace Oro\Bundle\DataGridBundle\Extension\Feature;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('features');

        $builder->getRootNode()
            ->children()
                ->scalarNode('entity_class_name_path')->end()
            ->end();

        return $builder;
    }
}
