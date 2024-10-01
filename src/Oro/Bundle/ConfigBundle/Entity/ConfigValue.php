<?php

namespace Oro\Bundle\ConfigBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;

/**
 * Config Value Entity class.
 */
#[ORM\Entity(repositoryClass: ConfigValueRepository::class)]
#[ORM\Table(name: 'oro_config_value')]
#[ORM\UniqueConstraint(name: 'CONFIG_VALUE_UQ_ENTITY', columns: ['name', 'section', 'config_id'])]
#[ORM\HasLifecycleCallbacks]
class ConfigValue
{
    const FIELD_SCALAR_TYPE = 'scalar';
    const FIELD_OBJECT_TYPE = 'object';
    const FIELD_ARRAY_TYPE  = 'array';

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Config::class, inversedBy: 'values')]
    #[ORM\JoinColumn(name: 'config_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Config $config = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    protected ?string $section = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'text_value', type: Types::TEXT, nullable: true)]
    protected $textValue;

    /**
     * @var string
     */
    #[ORM\Column(name: 'object_value', type: Types::OBJECT, nullable: true)]
    protected $objectValue;

    /**
     * @var string
     */
    #[ORM\Column(name: 'array_value', type: Types::ARRAY, nullable: true)]
    protected $arrayValue;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: false)]
    protected ?string $type = self::FIELD_SCALAR_TYPE;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set config
     *
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get config
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->clearValue();
        switch (true) {
            case is_object($value):
                $this->objectValue = clone $value;
                $this->type        = self::FIELD_OBJECT_TYPE;
                break;
            case is_array($value):
                $this->arrayValue = $value;
                $this->type       = self::FIELD_ARRAY_TYPE;
                break;
            default:
                $this->textValue = $value;
                $this->type      = self::FIELD_SCALAR_TYPE;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        switch ($this->type) {
            case self::FIELD_ARRAY_TYPE:
                return $this->arrayValue;
                break;
            case self::FIELD_OBJECT_TYPE:
                return $this->objectValue;
                break;
            default:
                return $this->textValue;
        }
    }

    /**
     * @param string $section
     *
     * @return $this
     */
    public function setSection($section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get created date/time
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get last update date/time
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return Config
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Pre persist event listener
     */
    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function doPreUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getValue();
    }

    /**
     * Clear all value types
     *
     * @return void
     */
    public function clearValue()
    {
        $this->objectValue = $this->arrayValue = $this->textValue = null;
    }
}
