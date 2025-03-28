<?php

namespace Oro\Component\Layout\Loader\Driver;

use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates layout update object and instantiate it based on yml configuration file content.
 * Config should contain "layout" root node that should consist with array of actions in "actions" node.
 * Extra keys are allowed and will be processed(or skipped) depends on generator.
 *
 * Example:
 *    layout:
 *        actions:
 *            - `@add`:
 *              id:        test
 *              parent:    root
 *              blockType: block
 *
 * @see src/Oro/Component/Layout/Tests/Unit/Extension/Theme/Stubs/Updates/layout_update4.yml
 */
class YamlDriver extends AbstractDriver
{
    #[\Override]
    protected function loadResourceGeneratorData($file)
    {
        $data = Yaml::parse(file_get_contents($file));
        $data = isset($data['layout']) ? $data['layout'] : [];

        return new GeneratorData($data, $file);
    }

    #[\Override]
    protected function dumpSource($source)
    {
        return Yaml::dump($source);
    }
}
