<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\DatabasePlatformMock that is excluded from doctrine
 * package since v2.4.
 */
class DatabasePlatformMock extends AbstractPlatform
{
    private $name = 'mock';
    private $sequenceNextValSql = '';
    private $prefersIdentityColumns = true;
    private $prefersSequences = false;
    private $reservedKeywordsClass = null;

    /**
     * @override
     */
    public function getNativeDeclaration(array $field)
    {
    }

    /**
     * @override
     */
    public function getPortableDeclaration(array $field)
    {
    }

    /**
     * @override
     */
    #[\Override]
    public function prefersIdentityColumns()
    {
        return $this->prefersIdentityColumns;
    }

    /**
     * @override
     */
    #[\Override]
    public function prefersSequences()
    {
        return $this->prefersSequences;
    }

    /** @override */
    #[\Override]
    public function getSequenceNextValSQL($sequenceName)
    {
        return $this->sequenceNextValSql;
    }

    /** @override */
    #[\Override]
    public function getBooleanTypeDeclarationSQL(array $field)
    {
    }

    /** @override */
    #[\Override]
    public function getIntegerTypeDeclarationSQL(array $field)
    {
    }

    /** @override */
    #[\Override]
    public function getBigIntTypeDeclarationSQL(array $field)
    {
    }

    /** @override */
    #[\Override]
    public function getSmallIntTypeDeclarationSQL(array $field)
    {
    }

    /** @override */
    // @codingStandardsIgnoreStart
    #[\Override]
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
        // @codingStandardsIgnoreEnd
    }

    /** @override */
    #[\Override]
    public function getVarcharTypeDeclarationSQL(array $field)
    {
    }

    /** @override */
    #[\Override]
    public function getClobTypeDeclarationSQL(array $field)
    {
    }

    /* MOCK API */

    public function setPrefersIdentityColumns($bool)
    {
        $this->_prefersIdentityColumns = $bool;
    }

    public function setPrefersSequences($bool)
    {
        $this->_prefersSequences = $bool;
    }

    public function setSequenceNextValSql($sql)
    {
        $this->_sequenceNextValSql = $sql;
    }

    #[\Override]
    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    #[\Override]
    protected function initializeDoctrineTypeMappings()
    {
    }

    /**
     * Gets the SQL Snippet used to declare a BLOB column type.
     */
    #[\Override]
    public function getBlobTypeDeclarationSQL(array $field)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    #[\Override]
    public function supportsSequences(): bool
    {
        return true;
    }

    #[\Override]
    public function supportsIdentityColumns(): bool
    {
        return $this->prefersIdentityColumns;
    }

    #[\Override]
    protected function getReservedKeywordsClass()
    {
        return $this->reservedKeywordsClass ?? parent::getReservedKeywordsClass();
    }

    public function setReservedKeywordsClass(?string $reservedKeywordsClass): void
    {
        $this->reservedKeywordsClass = $reservedKeywordsClass;
    }
}
