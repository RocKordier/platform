<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Schema;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Schema\Column;

/**
 * Adds handling of extended options to the Column class that is used in migrations.
 */
class ExtendColumn extends Column
{
    /** @var ExtendOptionsManager */
    protected $extendOptionsManager;

    /** @var string */
    protected $tableName;

    /** @var bool */
    protected $constructed = false;

    public function __construct(array $args)
    {
        $this->extendOptionsManager = $args['extendOptionsManager'];
        $this->tableName = $args['tableName'];

        parent::__construct($args);

        $this->constructed = true;
    }

    /**
     *
     * @return ExtendColumn
     */
    #[\Override]
    public function setOptions(array $options)
    {
        if (isset($options[OroOptions::KEY])) {
            $oroOptions = $options[OroOptions::KEY];
            if ($oroOptions instanceof OroOptions) {
                $oroOptions = $oroOptions->toArray();
            }
            if (!isset($options['type'])
                && !isset($oroOptions[ExtendOptionsManager::TYPE_OPTION])
                && null !== $this->getType()
            ) {
                $oroOptions[ExtendOptionsManager::TYPE_OPTION] = $this->getType()->getName();
            }
            $this->extendOptionsManager->setColumnOptions($this->tableName, $this->getName(), $oroOptions);
            unset($options[OroOptions::KEY]);
        }

        if (!empty($options)) {
            parent::setOptions($options);
        }

        return $this;
    }

    /**
     *
     * @return ExtendColumn
     */
    #[\Override]
    public function setNotnull($notnull)
    {
        if ($this->constructed) {
            $this->setOptions([OroOptions::KEY => ['extend' => ['nullable' => !$notnull]]]);
        }

        return parent::setNotnull($notnull);
    }

    /**
     *
     * @return ExtendColumn
     */
    #[\Override]
    public function setType(Type $type)
    {
        if ($this->constructed) {
            $this->setOptions([OroOptions::KEY => [ExtendOptionsManager::TYPE_OPTION => $type->getName()]]);
        }

        return parent::setType($type);
    }

    /**
     *
     * @return ExtendColumn
     */
    #[\Override]
    public function setLength($length)
    {
        if ($this->constructed) {
            $this->setOptions([OroOptions::KEY => ['extend' => ['length' => $length]]]);
        }

        return parent::setLength($length);
    }

    /**
     *
     * @return ExtendColumn
     */
    #[\Override]
    public function setPrecision($precision)
    {
        if ($this->constructed) {
            $this->setOptions([OroOptions::KEY => ['extend' => ['precision' => $precision]]]);
        }

        return parent::setPrecision($precision);
    }

    /**
     *
     * @return ExtendColumn
     */
    #[\Override]
    public function setScale($scale)
    {
        if ($this->constructed) {
            $this->setOptions([OroOptions::KEY => ['extend' => ['scale' => $scale]]]);
        }

        return parent::setScale($scale);
    }

    /**
     *
     * @return ExtendColumn
     */
    #[\Override]
    public function setDefault($default)
    {
        if ($this->constructed) {
            $this->setOptions([OroOptions::KEY => ['extend' => ['default' => $default]]]);
        }

        return parent::setDefault($default);
    }

    /**
     * @return ExtendColumn
     */
    public function enableExtendOptions()
    {
        $this->constructed = true;

        return $this;
    }

    /**
     * @return ExtendColumn
     */
    public function disableExtendOptions()
    {
        $this->constructed = false;

        return $this;
    }
}
