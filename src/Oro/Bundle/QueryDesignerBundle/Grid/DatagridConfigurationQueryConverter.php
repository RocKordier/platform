<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\GroupingOrmQueryConverter;

/**
 * Converts a query definition created by the query designer to a data grid configuration.
 */
class DatagridConfigurationQueryConverter extends GroupingOrmQueryConverter
{
    /** @var DatagridGuesser */
    protected $datagridGuesser;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        VirtualRelationProviderInterface $virtualRelationProvider,
        DoctrineHelper $doctrineHelper,
        DatagridGuesser $datagridGuesser,
        EntityNameResolver $entityNameResolver,
        protected EnumTypeHelper $enumTypeHelper
    ) {
        parent::__construct($functionProvider, $virtualFieldProvider, $virtualRelationProvider, $doctrineHelper);
        $this->datagridGuesser = $datagridGuesser;
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * Converts a query specified in $source parameter to a datagrid configuration
     */
    public function convert(string $gridName, AbstractQueryDesigner $source): DatagridConfiguration
    {
        $config = DatagridConfiguration::createNamed(
            $gridName,
            [],
            PropertyAccess::createPropertyAccessorWithDotSyntax()
        );
        $config->setDatasourceType(OrmDatasource::TYPE);

        $this->context()->setConfig($config);
        $this->doConvert($source);

        return $config;
    }

    #[\Override]
    protected function createContext(): DatagridConfigurationQueryConverterContext
    {
        return new DatagridConfigurationQueryConverterContext();
    }

    #[\Override]
    protected function context(): DatagridConfigurationQueryConverterContext
    {
        return parent::context();
    }

    #[\Override]
    protected function saveTableAliases(array $tableAliases): void
    {
        $this->context()->getConfig()->offsetSetByPath(
            QueryDesignerQueryConfiguration::TABLE_ALIASES,
            $tableAliases
        );
    }

    #[\Override]
    protected function saveColumnAliases(array $columnAliases): void
    {
        $this->context()->getConfig()->offsetSetByPath(
            QueryDesignerQueryConfiguration::COLUMN_ALIASES,
            $columnAliases
        );
    }

    #[\Override]
    protected function addSelectStatement(): void
    {
        parent::addSelectStatement();
        $this->context()->getConfig()->getOrmQuery()->setSelect($this->context()->getSelectColumns());
    }

    #[\Override]
    protected function addSelectColumn(
        string $entityClass,
        string $tableAlias,
        string $fieldName,
        string $columnExpr,
        string $columnAlias,
        string $columnLabel,
        $functionExpr,
        ?string $functionReturnType,
        bool $isDistinct
    ): void {
        if ($isDistinct) {
            $columnExpr = 'DISTINCT ' . $columnExpr;
        }

        if (null !== $functionExpr) {
            $functionExpr = $this->prepareFunctionExpression(
                $functionExpr,
                $tableAlias,
                $fieldName,
                $columnExpr,
                $columnAlias
            );
        }

        $fieldType = $functionReturnType ?? $this->getFieldType($entityClass, $fieldName);

        if ($this->enumTypeHelper->getEnumCode($entityClass, $fieldName) !== null) {
            $fieldType = $this->enumTypeHelper->getEnumFieldType($entityClass, $fieldName);
        }

        if (!$functionExpr && 'dictionary' === $fieldType) {
            [$entityAlias] = explode('.', $columnExpr);
            $nameDql = $this->entityNameResolver->getNameDQL(
                $this->getTargetEntityClass($entityClass, $fieldName),
                $entityAlias
            );
            if ($nameDql) {
                $columnExpr = $nameDql;
            }
        }

        $this->context()->addSelectColumn(sprintf('%s as %s', $functionExpr ?? $columnExpr, $columnAlias));

        $columnOptions = [
            DatagridGuesser::FORMATTER => [
                'label'        => $columnLabel,
                'translatable' => false
            ],
            DatagridGuesser::SORTER    => [
                'data_name' => $columnAlias
            ],
            DatagridGuesser::FILTER    => [
                'data_name'    => $this->getFilterByExpr($entityClass, $tableAlias, $fieldName, $columnAlias),
                'translatable' => false
            ],
        ];
        $groupingColumns = $this->context()->getDefinition()['grouping_columns'] ?? null;
        if (null !== $functionExpr && $groupingColumns) {
            $columnOptions[DatagridGuesser::FILTER][FilterUtility::BY_HAVING_KEY] = true;
        }
        $this->datagridGuesser->applyColumnGuesses($entityClass, $fieldName, $fieldType, $columnOptions);
        $this->datagridGuesser->setColumnOptions($this->context()->getConfig(), $columnAlias, $columnOptions);

        $this->context()->getConfig()->offsetSetByPath(
            sprintf('[fields_acl][columns][%s][data_name]', $columnAlias),
            $columnExpr
        );
    }

    /**
     * Get target entity class for virtual field.
     */
    protected function getTargetEntityClass(string $entityClass, string $fieldName): string
    {
        if ($this->isVirtualField($entityClass, $fieldName)) {
            $columnJoinId = $this->buildColumnJoinIdentifier($fieldName, $entityClass);
            if ($this->context()->hasVirtualColumnOption($columnJoinId, 'related_entity_name')) {
                return $this->context()->getVirtualColumnOption($columnJoinId, 'related_entity_name');
            }
        }

        return $entityClass;
    }

    #[\Override]
    protected function addFromStatements(): void
    {
        parent::addFromStatements();
        $this->context()->getConfig()->getOrmQuery()->setFrom($this->context()->getFrom());
    }

    #[\Override]
    protected function addFromStatement(string $entityClass, string $tableAlias): void
    {
        $this->context()->addFrom($entityClass, $tableAlias);
    }

    #[\Override]
    protected function addJoinStatements(): void
    {
        parent::addJoinStatements();
        $innerJoins = $this->context()->getInnerJoins();
        if ($innerJoins) {
            $this->context()->getConfig()->getOrmQuery()->setInnerJoins($innerJoins);
        }
        $leftJoins = $this->context()->getLeftJoins();
        if ($leftJoins) {
            $this->context()->getConfig()->getOrmQuery()->setLeftJoins($leftJoins);
        }
    }

    #[\Override]
    protected function addJoinStatement(
        ?string $joinType,
        string $join,
        string $joinAlias,
        ?string $joinConditionType,
        ?string $joinCondition
    ): void {
        if (self::LEFT_JOIN === $joinType) {
            $this->context()->addLeftJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        } else {
            $this->context()->addInnerJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        }
    }

    #[\Override]
    protected function addWhereStatement(): void
    {
        parent::addWhereStatement();
        $filters = $this->context()->getFilters();
        if ($filters) {
            $this->context()->getConfig()->offsetSetByPath(QueryDesignerQueryConfiguration::FILTERS, $filters);
        }
    }

    #[\Override]
    protected function addGroupByStatement(): void
    {
        parent::addGroupByStatement();
        $groupingColumns = $this->context()->getGroupingColumns();
        if ($groupingColumns) {
            $this->context()->getConfig()->getOrmQuery()->setGroupBy(implode(', ', $groupingColumns));
        }
    }

    #[\Override]
    protected function addGroupByColumn(string $columnAlias): void
    {
        $this->context()->addGroupingColumn($columnAlias);
    }

    #[\Override]
    protected function addOrderByColumn(string $columnAlias, string $columnSorting): void
    {
        $this->context()->getConfig()->offsetSetByPath(
            sprintf('[sorters][default][%s]', $columnAlias),
            $columnSorting
        );
    }
}
