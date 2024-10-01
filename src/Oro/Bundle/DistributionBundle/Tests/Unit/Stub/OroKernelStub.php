<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Stub;

use Oro\Bundle\DistributionBundle\OroKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class OroKernelStub extends OroKernel
{
    /** @var string */
    private $appDir;

    public function setAppDir(string $appDir)
    {
        $this->appDir = $appDir;
    }

    #[\Override]
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getProjectDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }

    public function getFixturesDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;
    }

    #[\Override]
    public function getProjectDir(): string
    {
        $dir =  __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures';

        return $dir . ($this->appDir ? '/' . $this->appDir : '');
    }

    #[\Override]
    public function getCacheDir(): string
    {
        $appDir = ($this->appDir ? '/' . $this->appDir : '');

        return sys_get_temp_dir() . $appDir . '/var/cache/' . $this->environment;
    }

    #[\Override]
    public function getLogDir(): string
    {
        $appDir = ($this->appDir ? '/' . $this->appDir : '');

        return sys_get_temp_dir() . $appDir . '/var/log';
    }

    #[\Override]
    protected function findBundles($roots = [])
    {
        return [
            $this->getFixturesDir() . '/bundles1.yml',
            $this->getFixturesDir() . '/bundles2.yml',
            $this->getFixturesDir() . '/bundles3.yml',
        ];
    }

    #[\Override]
    public function registerBundles(): iterable
    {
        return array_map(
            function (array $params) {
                return new BundleStub($params['name']);
            },
            array_values(
                $this->collectBundles()
            )
        );
    }

    public function setBundleMap(array $bundleMap)
    {
        $this->bundleMap = $bundleMap;
    }
}
