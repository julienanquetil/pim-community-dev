<?php
namespace Pim\Bundle\CatalogBundle\Entity;

use Bap\Bundle\FlexibleEntityBundle\Model\Entity as AbstractEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Flexible product
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright Copyright (c) 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @ORM\Table(name="Akeneo_PimCatalog_Product_Entity")
 * @ORM\Entity
 */
class ProductEntity extends AbstractEntity
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
     * @var EntityType $type
     *
     * @ORM\ManyToOne(targetEntity="ProductType")
     */
    protected $type;

    /**
     * @var Value
     *
     * @ORM\OneToMany(targetEntity="ProductValue", mappedBy="product", cascade={"persist", "remove"})
     */
    protected $values;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->values = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add values
     *
     * @param Pim\Bundle\CatalogBundle\Entity\ProductValue $values
     * @return ProductEntity
     */
    public function addValue(\Pim\Bundle\CatalogBundle\Entity\ProductValue $values)
    {
        $this->values[] = $values;

        return $this;
    }

    /**
     * Remove values
     *
     * @param Pim\Bundle\CatalogBundle\Entity\ProductValue $values
     */
    public function removeValue(\Pim\Bundle\CatalogBundle\Entity\ProductValue $values)
    {
        $this->values->removeElement($values);
    }

    /**
     * Get values
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getValues()
    {
        return $this->values;
    }
}