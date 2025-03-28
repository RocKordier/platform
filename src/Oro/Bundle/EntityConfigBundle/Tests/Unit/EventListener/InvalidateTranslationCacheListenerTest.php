<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\EventListener\InvalidateTranslationCacheListener;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheProvider;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class InvalidateTranslationCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineAclCacheProvider  */
    protected $queryCacheProvider;

    /** @var InvalidateTranslationCacheListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->queryCacheProvider = $this->createMock(DoctrineAclCacheProvider::class);

        $this->listener = new InvalidateTranslationCacheListener($this->doctrine, $this->queryCacheProvider);
    }

    public function testOnInvalidateDynamicTranslationCacheWhenNoClearableCacheProvider(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(EnumOption::class)
            ->willReturn($entityManager);

        $configuration = new Configuration();
        $configuration->setQueryCache($this->createMock(AbstractAdapter::class));

        $entityManager->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->queryCacheProvider->expects(self::once())
            ->method('clear');

        $this->listener->onInvalidateDynamicTranslationCache();
    }

    public function testOnInvalidateDynamicTranslationCache()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(EnumOption::class)
            ->willReturn($entityManager);

        $configuration = new Configuration();
        $entityManager->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $cacheProvider = $this->createMock(AbstractAdapter::class);
        $configuration->setQueryCache($cacheProvider);
        $cacheProvider->expects($this->once())
            ->method('clear');
        $this->queryCacheProvider->expects(self::once())
            ->method('clear');

        $this->listener->onInvalidateDynamicTranslationCache();
    }
}
