<?php

namespace Pim\Bundle\CatalogBundle\Doctrine\ORM\Filter;

use Pim\Bundle\CatalogBundle\Doctrine\Query\Operators;
use Pim\Bundle\CatalogBundle\Doctrine\Query\AttributeFilterInterface;
use Pim\Bundle\CatalogBundle\Model\AttributeInterface;

/**
 * Number filter
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class NumberFilter extends AbstractFilter implements AttributeFilterInterface
{
    /** @var array */
    protected $supportedAttributes;

    /**
     * Instanciate the base filter
     *
     * @param array $supportedAttributes
     * @param array $supportedOperators
     */
    public function __construct(
        array $supportedAttributes = [],
        array $supportedOperators = []
    ) {
        $this->supportedAttributes = $supportedAttributes;
        $this->supportedOperators  = $supportedOperators;
    }

    /**
     * {@inheritdoc}
     */
    public function addAttributeFilter(AttributeInterface $attribute, $operator, $value, $locale = null, $scope = null)
    {
        $joinAlias = 'filter'.$attribute->getCode();
        $backendField = sprintf('%s.%s', $joinAlias, $attribute->getBackendType());

        if ($operator === Operators::IS_EMPTY) {
            $this->qb->leftJoin(
                $this->qb->getRootAlias().'.values',
                $joinAlias,
                'WITH',
                $this->prepareAttributeJoinCondition($attribute, $joinAlias, $locale, $scope)
            );
            $this->qb->andWhere($this->prepareCriteriaCondition($backendField, $operator, $value));
        } else {
            $condition = $this->prepareAttributeJoinCondition($attribute, $joinAlias, $locale, $scope);
            $condition .= ' AND '.$this->prepareCriteriaCondition($backendField, $operator, $value);
            $this->qb->innerJoin(
                $this->qb->getRootAlias().'.values',
                $joinAlias,
                'WITH',
                $condition
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute(AttributeInterface $attribute)
    {
        return in_array($attribute->getAttributeType(), $this->supportedAttributes);
    }
}