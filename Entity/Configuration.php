<?php
namespace Oro\Bundle\DataFlowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity configuration
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 *
 * @ORM\Table(
 *     name="oro_dataflow_configuration", indexes={@ORM\Index(name="searchcode_idx", columns={"description"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="searchunique_idx", columns={"description", "type_name"})}
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\DataFlowBundle\Entity\Repository\ConfigurationRepository")
 */
class Configuration
{

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Description is unique per type
     *
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="type_name", type="string", length=255)
     */
    protected $typeName;

    /**
     * @var string
     *
     * @ORM\Column(name="format", type="string", length=20)
     */
    protected $format;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text")
     */
    protected $data;

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
     * Set description
     *
     * @param string $description
     *
     * @return Configuration
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set type name
     *
     * @param string $type
     *
     * @return Configuration
     */
    public function setTypeName($type)
    {
        $this->typeName = $type;

        return $this;
    }

    /**
     * Get type name
     *
     * @return string
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * Set format
     *
     * @param string $format
     *
     * @return Configuration
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set data
     *
     * @param string $data
     *
     * @return Configuration
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
}
