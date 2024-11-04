<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationNameGenerator as NameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityStaticCache;

/**
 * Abstract Extended Entity Field Processor Associations Extension
 */
abstract class AbstractAssociationEntityFieldExtension implements EntityFieldExtensionInterface
{
    protected const OBJECT_INDEX = 0;
    protected const METHOD_INDEX = 1;
    public const TYPE_INDEX = 2;

    abstract public function isApplicable(EntityFieldProcessTransport $transport): bool;

    abstract public function getRelationKind(): ?string;

    abstract public function getRelationType(): string;

    protected function getMethodsData(EntityFieldProcessTransport $transport): array
    {
        if (!$this->isApplicable($transport)) {
            return [];
        }
        $methods = [
            'getAssociationRelationType' => [$this, 'callGetRelationType', 'string'],
            'getAssociationRelationKind' => [$this, 'callGetRelationKind', '?string'],
        ];

        switch ($this->getRelationType()) {
            case RelationType::MANY_TO_MANY:
            case RelationType::MULTIPLE_MANY_TO_ONE:
                $methods[NameGenerator::generateSupportTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callSupport',
                    'bool'
                ];
                $methods[NameGenerator::generateGetTargetsMethodName($this->getRelationKind())] = [
                    $this,
                    'callGetTargets',
                    'array|object'
                ];
                $methods[NameGenerator::generateHasTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callHasTarget',
                    'bool'
                ];
                $methods[NameGenerator::generateAddTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callAddTarget',
                    'self'
                ];
                $methods[NameGenerator::generateRemoveTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callRemoveTarget',
                    'self'
                ];
                break;
            case RelationType::MANY_TO_ONE:
                $methods[NameGenerator::generateSupportTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callSupport',
                    'bool'
                ];
                $methods[NameGenerator::generateGetTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callGetTarget',
                    '?object'
                ];
                $methods[NameGenerator::generateSetTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callSetTarget',
                    'self'
                ];
                break;
        }

        return $methods;
    }

    #[\Override]
    public function getMethods(EntityFieldProcessTransport $transport): array
    {
        return array_keys($this->getMethodsData($transport));
    }

    protected function callGetRelationType(EntityFieldProcessTransport $transport): void
    {
        $transport->setResult($this->getRelationType());
    }

    protected function callGetRelationKind(EntityFieldProcessTransport $transport): void
    {
        $transport->setResult($this->getRelationKind());
    }

    protected function callSupport(EntityFieldProcessTransport $transport): void
    {
        $result = AssociationExtendEntity::support($transport->getObject(), $transport->getArgument(0), $this);
        $transport->setResult($result);
    }

    protected function callGetTargets(EntityFieldProcessTransport $transport): void
    {
        $result = AssociationExtendEntity::getTargets($transport->getObject(), $this, $transport->getArgument(0));
        $transport->setResult($result);
    }

    protected function callGetTarget(EntityFieldProcessTransport $transport): void
    {
        $result = AssociationExtendEntity::getTarget($transport->getObject(), $this);
        $transport->setResult($result);
    }

    protected function callSetTarget(EntityFieldProcessTransport $transport): void
    {
        AssociationExtendEntity::setTarget($transport->getObject(), $this, $transport->getArgument(0));
        $transport->setResult($transport->getObject());
    }

    protected function callHasTarget(EntityFieldProcessTransport $transport): void
    {
        $result = AssociationExtendEntity::hasTarget($transport->getObject(), $transport->getArgument(0), $this);
        $transport->setResult($result);
    }

    protected function callAddTarget(EntityFieldProcessTransport $transport): void
    {
        AssociationExtendEntity::addTarget($transport->getObject(), $transport->getArgument(0), $this);
        $transport->setResult($transport->getObject());
    }

    protected function callRemoveTarget(EntityFieldProcessTransport $transport): void
    {
        AssociationExtendEntity::removeTarget($transport->getObject(), $transport->getArgument(0), $this);
        $transport->setResult($transport->getObject());
    }

    #[\Override]
    public function get(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport)) {
            return;
        }
        if ($transport->getName() !== 'target') {
            return;
        }

        $this->callGetTarget($transport);

        $transport->setProcessed(true);
    }

    #[\Override]
    public function set(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport)) {
            return;
        }

        if ($transport->getName() !== 'target') {
            return;
        }
        $transport->setArguments([$transport->getValue()]);

        $this->callSetTarget($transport);

        $transport->setProcessed(true);
    }

    #[\Override]
    public function call(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport)) {
            return;
        }

        $methods = $this->getMethodsData($transport);
        if (isset($methods[$transport->getName()])) {
            call_user_func(
                [
                    $methods[$transport->getName()][self::OBJECT_INDEX],
                    $methods[$transport->getName()][self::METHOD_INDEX]
                ],
                $transport
            );
            $transport->setProcessed(true);
        }
    }

    #[\Override]
    public function isset(EntityFieldProcessTransport $transport): void
    {
        $this->propertyExists($transport);
    }

    #[\Override]
    public function propertyExists(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport)) {
            return;
        }

        if ($transport->getName() === 'target') {
            $transport->setResult(true);
            $transport->setProcessed(true);
        }
    }

    #[\Override]
    public function methodExists(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport)) {
            return;
        }
        // get a list of associated methods
        if (EntityPropertyInfo::isMethodMatchExists($this->getMethods($transport), $transport->getName())) {
            $transport->setResult(true);
            $transport->setProcessed(true);
            ExtendEntityStaticCache::setMethodExistsCache($transport, true);
        }
    }

    #[\Override]
    public function getMethodInfo(EntityFieldProcessTransport $transport): void
    {
        $methods = $this->getMethodsData($transport);
        if (!isset($methods[$transport->getName()])) {
            return;
        }
        $transport->setResult([
            ExtendEntityMetadataProvider::FIELD_NAME => $this->getRelationKind(),
            ExtendEntityMetadataProvider::FIELD_TYPE => $methods[$transport->getName()][self::TYPE_INDEX],
            ExtendEntityMetadataProvider::IS_EXTEND => true,
            ExtendEntityMetadataProvider::IS_NULLABLE => str_starts_with(
                $methods[$transport->getName()][self::TYPE_INDEX],
                '?'
            ),
            ExtendEntityMetadataProvider::IS_SERIALIZED => false,
        ]);
        $transport->setProcessed(true);
    }
}
