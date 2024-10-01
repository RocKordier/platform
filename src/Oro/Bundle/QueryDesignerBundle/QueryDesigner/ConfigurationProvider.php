<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\FolderYamlCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for query designer configuration
 * that is loaded from "Resources/config/oro/query_designer.yml" files.
 */
class ConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/query_designer.yml';
    private const APP_CONFIG_PATH = '../config/oro/query_designer';

    private Configuration $configuration;

    public function __construct(
        string $cacheFile,
        bool $debug,
        Configuration $configuration
    ) {
        parent::__construct($cacheFile, $debug);
        $this->configuration = $configuration;
    }

    /**
     * Gets query designer configuration.
     */
    public function getConfiguration(): array
    {
        return $this->doGetConfig();
    }

    #[\Override]
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer): array
    {
        $configs = [];
        $configLoader = new CumulativeConfigLoader(
            'oro_query_designer',
            [
                new YamlCumulativeFileLoader(self::CONFIG_FILE),
                new FolderYamlCumulativeFileLoader(self::APP_CONFIG_PATH),
            ]
        );
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            $config = $resource->data[Configuration::ROOT_NODE_NAME];
            $vendor = strtolower(substr($resource->bundleClass, 0, strpos($resource->bundleClass, '\\')));
            $this->updateLabelsOfFunctions($config, 'converters', $vendor);
            $this->updateLabelsOfFunctions($config, 'aggregates', $vendor);
            $configs[] = $config;
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            implode(', ', [self::CONFIG_FILE, self::APP_CONFIG_PATH]),
            $this->configuration,
            $configs
        );
    }

    private function updateLabelsOfFunctions(array &$config, string $groupType, string $vendor): void
    {
        if (isset($config[$groupType])) {
            foreach ($config[$groupType] as $groupName => &$group) {
                if (isset($group['functions'])) {
                    foreach ($group['functions'] as &$func) {
                        $this->updateFunctionLabel($func, 'name', $vendor, $groupType, $groupName);
                        $this->updateFunctionLabel($func, 'hint', $vendor, $groupType, $groupName);
                    }
                }
            }
        }
    }

    private function updateFunctionLabel(
        array &$func,
        string $labelType,
        string $vendor,
        string $groupType,
        string $groupName
    ): void {
        $labelName = $labelType . '_label';
        if (!isset($func[$labelName])) {
            $func[$labelName] = sprintf(
                '%s.query_designer.%s.%s.%s.%s',
                $vendor,
                $groupType,
                $groupName,
                $func['name'],
                $labelType
            );
        } elseif ($func[$labelName] === true) {
            // this function should use a label of overridden function
            $func[$labelName] = '';
        }
    }
}
