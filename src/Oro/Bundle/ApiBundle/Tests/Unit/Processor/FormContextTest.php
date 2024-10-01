<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\MetaPropertiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FormContextTest extends \PHPUnit\Framework\TestCase
{
    private FormContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new FormContextStub($configProvider, $metadataProvider);
    }

    public function testRequestId()
    {
        self::assertNull($this->context->getRequestId());

        $requestId = 'test';
        $this->context->setRequestId($requestId);
        self::assertSame($requestId, $this->context->getRequestId());
    }

    public function testRequestData()
    {
        self::assertSame([], $this->context->getRequestData());

        $requestData = ['key' => 'val'];
        $this->context->setRequestData($requestData);
        self::assertSame($requestData, $this->context->getRequestData());
    }

    public function testExisting()
    {
        self::assertFalse($this->context->isExisting());

        $this->context->setExisting(true);
        self::assertTrue($this->context->isExisting());
    }

    public function testIncludedData()
    {
        $includedData = [];
        $this->context->setIncludedData($includedData);
        self::assertSame($includedData, $this->context->getIncludedData());
    }

    public function testIncludedEntities()
    {
        self::assertNull($this->context->getIncludedEntities());

        $includedEntities = $this->createMock(IncludedEntityCollection::class);
        $this->context->setIncludedEntities($includedEntities);
        self::assertSame($includedEntities, $this->context->getIncludedEntities());

        $this->context->setIncludedEntities(null);
        self::assertNull($this->context->getIncludedEntities());
    }

    public function testAdditionalEntities()
    {
        self::assertSame([], $this->context->getAdditionalEntities());

        $entity1 = new \stdClass();
        $entity2 = new \stdClass();
        $entity3 = new \stdClass();

        $this->context->addAdditionalEntity($entity1);
        $this->context->addAdditionalEntity($entity2);
        self::assertSame([$entity1, $entity2], $this->context->getAdditionalEntities());

        $this->context->addAdditionalEntityToRemove($entity3);
        self::assertSame([$entity1, $entity2, $entity3], $this->context->getAdditionalEntities());

        self::assertFalse($this->context->getAdditionalEntityCollection()->shouldEntityBeRemoved($entity1));
        self::assertFalse($this->context->getAdditionalEntityCollection()->shouldEntityBeRemoved($entity2));
        self::assertTrue($this->context->getAdditionalEntityCollection()->shouldEntityBeRemoved($entity3));

        $this->context->addAdditionalEntity($entity1);
        $this->context->addAdditionalEntityToRemove($entity3);
        self::assertSame([$entity1, $entity2, $entity3], $this->context->getAdditionalEntities());

        $this->context->removeAdditionalEntity($entity1);
        self::assertSame([$entity2, $entity3], $this->context->getAdditionalEntities());

        $this->context->removeAdditionalEntity($entity3);
        self::assertSame([$entity2], $this->context->getAdditionalEntities());

        $this->context->removeAdditionalEntity($entity2);
        self::assertSame([], $this->context->getAdditionalEntities());
    }

    public function testEntityMapper()
    {
        $entityMapper = $this->createMock(EntityMapper::class);

        self::assertNull($this->context->getEntityMapper());

        $this->context->setEntityMapper($entityMapper);
        self::assertSame($entityMapper, $this->context->getEntityMapper());

        $this->context->setEntityMapper(null);
        self::assertNull($this->context->getEntityMapper());
    }

    public function testFormBuilder()
    {
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        self::assertFalse($this->context->hasFormBuilder());
        self::assertNull($this->context->getFormBuilder());

        $this->context->setFormBuilder($formBuilder);
        self::assertTrue($this->context->hasFormBuilder());
        self::assertSame($formBuilder, $this->context->getFormBuilder());

        $this->context->setFormBuilder(null);
        self::assertFalse($this->context->hasFormBuilder());
        self::assertNull($this->context->getFormBuilder());
    }

    public function testForm()
    {
        $form = $this->createMock(FormInterface::class);

        self::assertFalse($this->context->hasForm());
        self::assertNull($this->context->getForm());

        $this->context->setForm($form);
        self::assertTrue($this->context->hasForm());
        self::assertSame($form, $this->context->getForm());

        $this->context->setForm(null);
        self::assertFalse($this->context->hasForm());
        self::assertNull($this->context->getForm());
    }

    public function testSkipFormValidation()
    {
        self::assertFalse($this->context->isFormValidationSkipped());

        $this->context->skipFormValidation(true);
        self::assertTrue($this->context->isFormValidationSkipped());

        $this->context->skipFormValidation(false);
        self::assertFalse($this->context->isFormValidationSkipped());
    }

    public function testSetConfigExtras()
    {
        $normalizedExpandRelatedEntitiesConfigExtra = new ExpandRelatedEntitiesConfigExtra(['association1']);
        $normalizedFilterFieldsConfigExtra = new FilterFieldsConfigExtra(['entity1' => ['field1']]);
        $normalizedMetaPropertiesConfigExtra = new MetaPropertiesConfigExtra();
        $normalizedMetaPropertiesConfigExtra->addMetaProperty('property1', 'string');
        $normalizedAnotherConfigExtra = new TestConfigExtra('extra1');
        $this->context->setNormalizedEntityConfigExtras([
            $normalizedExpandRelatedEntitiesConfigExtra,
            $normalizedFilterFieldsConfigExtra,
            $normalizedMetaPropertiesConfigExtra,
            $normalizedAnotherConfigExtra
        ]);
        self::assertSame(
            [
                $normalizedExpandRelatedEntitiesConfigExtra,
                $normalizedFilterFieldsConfigExtra,
                $normalizedMetaPropertiesConfigExtra,
                $normalizedAnotherConfigExtra
            ],
            $this->context->getNormalizedEntityConfigExtras()
        );
        self::assertSame([], $this->context->getConfigExtras());

        $expandRelatedEntitiesConfigExtra = new ExpandRelatedEntitiesConfigExtra(['association2']);
        $filterFieldsConfigExtra = new FilterFieldsConfigExtra(['entity2' => ['field2']]);
        $metaPropertiesConfigExtra = new MetaPropertiesConfigExtra();
        $metaPropertiesConfigExtra->addMetaProperty('property2', 'string');
        $anotherConfigExtra = new TestConfigExtra('extra2');
        $this->context->setConfigExtras([
            $expandRelatedEntitiesConfigExtra,
            $filterFieldsConfigExtra,
            $metaPropertiesConfigExtra,
            $anotherConfigExtra
        ]);
        self::assertSame(
            [
                $expandRelatedEntitiesConfigExtra,
                $filterFieldsConfigExtra,
                $metaPropertiesConfigExtra,
                $normalizedAnotherConfigExtra
            ],
            $this->context->getNormalizedEntityConfigExtras()
        );
        self::assertEquals(
            [
                new ExpandRelatedEntitiesConfigExtra([]),
                new FilterFieldsConfigExtra(['entity2' => null]),
                $anotherConfigExtra
            ],
            $this->context->getConfigExtras()
        );
    }

    public function testAddAndRemoveConfigExtra()
    {
        $normalizedExpandRelatedEntitiesConfigExtra = new ExpandRelatedEntitiesConfigExtra(['association1']);
        $normalizedFilterFieldsConfigExtra = new FilterFieldsConfigExtra(['entity1' => ['field1']]);
        $normalizedMetaPropertiesConfigExtra = new MetaPropertiesConfigExtra();
        $normalizedMetaPropertiesConfigExtra->addMetaProperty('property1', 'string');
        $normalizedAnotherConfigExtra = new TestConfigExtra('extra1');
        $this->context->setNormalizedEntityConfigExtras([
            $normalizedExpandRelatedEntitiesConfigExtra,
            $normalizedFilterFieldsConfigExtra,
            $normalizedMetaPropertiesConfigExtra,
            $normalizedAnotherConfigExtra
        ]);
        self::assertSame(
            [
                $normalizedExpandRelatedEntitiesConfigExtra,
                $normalizedFilterFieldsConfigExtra,
                $normalizedMetaPropertiesConfigExtra,
                $normalizedAnotherConfigExtra
            ],
            $this->context->getNormalizedEntityConfigExtras()
        );
        self::assertSame([], $this->context->getConfigExtras());

        $expandRelatedEntitiesConfigExtra = new ExpandRelatedEntitiesConfigExtra(['association2']);
        $filterFieldsConfigExtra = new FilterFieldsConfigExtra(['entity2' => ['field2']]);
        $metaPropertiesConfigExtra = new MetaPropertiesConfigExtra();
        $metaPropertiesConfigExtra->addMetaProperty('property2', 'string');
        $anotherConfigExtra = new TestConfigExtra('extra2');
        $this->context->addConfigExtra($expandRelatedEntitiesConfigExtra);
        $this->context->addConfigExtra($filterFieldsConfigExtra);
        $this->context->addConfigExtra($metaPropertiesConfigExtra);
        $this->context->addConfigExtra($anotherConfigExtra);
        self::assertSame(
            [
                $expandRelatedEntitiesConfigExtra,
                $filterFieldsConfigExtra,
                $metaPropertiesConfigExtra,
                $normalizedAnotherConfigExtra
            ],
            $this->context->getNormalizedEntityConfigExtras()
        );
        self::assertEquals(
            [
                new ExpandRelatedEntitiesConfigExtra([]),
                new FilterFieldsConfigExtra(['entity2' => null]),
                $anotherConfigExtra
            ],
            $this->context->getConfigExtras()
        );

        $this->context->removeConfigExtra($expandRelatedEntitiesConfigExtra->getName());
        $this->context->removeConfigExtra($filterFieldsConfigExtra->getName());
        $this->context->removeConfigExtra($metaPropertiesConfigExtra->getName());
        $this->context->removeConfigExtra($anotherConfigExtra->getName());
        self::assertSame(
            [
                $normalizedExpandRelatedEntitiesConfigExtra,
                $normalizedFilterFieldsConfigExtra,
                $normalizedMetaPropertiesConfigExtra,
                $normalizedAnotherConfigExtra
            ],
            $this->context->getNormalizedEntityConfigExtras()
        );
        self::assertSame([], $this->context->getConfigExtras());
    }

    public function testNormalizedEntityConfigExtras()
    {
        self::assertSame([], $this->context->getNormalizedEntityConfigExtras());

        $configExtra = new TestConfigExtra('extra1');
        $this->context->setNormalizedEntityConfigExtras([$configExtra]);
        self::assertSame([$configExtra], $this->context->getNormalizedEntityConfigExtras());
    }

    public function testGetAllEntities()
    {
        self::assertSame([], $this->context->getAllEntities());
        self::assertSame([], $this->context->getAllEntities(true));

        $entity = new \stdClass();
        $this->context->setResult($entity);
        self::assertSame([$entity], $this->context->getAllEntities());
        self::assertSame([$entity], $this->context->getAllEntities(true));

        $includedEntity = new \stdClass();
        $this->context->setIncludedEntities(new IncludedEntityCollection());
        $this->context->getIncludedEntities()
            ->add($includedEntity, \stdClass::class, 1, $this->createMock(IncludedEntityData::class));
        self::assertSame([$entity, $includedEntity], $this->context->getAllEntities());
        self::assertSame([$entity], $this->context->getAllEntities(true));
    }
}
