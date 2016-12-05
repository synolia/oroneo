<?php

namespace Synolia\Bundle\OroneoBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Synolia\Bundle\OroneoBundle\Manager\OroFieldSelectManager;

/**
 * Class ProductMappingType
 * @package Synolia\Bundle\OroneoBundle\Form\Type
 */
class ProductMappingType extends MappingType
{
    const NAME = 'synolia_Oroneo_product_mapping_type';

    /**
     * @var OroFieldSelectManager $oroFieldChoices
     */
    protected $oroFieldChoices;

    /**
     * MappingType constructor.
     *
     * @param OroFieldSelectManager $oroFieldChoices
     */
    public function __construct(OroFieldSelectManager $oroFieldChoices)
    {
        $this->oroFieldChoices = $oroFieldChoices;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            MappingType::ORO_FIELD,
            'choice',
            [
                'choices' => $this->oroFieldChoices->getChoices(Product::class),
            ]
        );
    }
}
