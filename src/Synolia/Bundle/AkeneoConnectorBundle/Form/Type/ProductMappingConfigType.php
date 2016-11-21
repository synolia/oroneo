<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ProductMappingConfigType
 * @package Synolia\Bundle\AkeneoConnectorBundle\Form\Type
 */
class ProductMappingConfigType extends AbstractType
{
    const NAME = 'synolia_akeneoconnector_product_mapping';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => ProductMappingType::NAME,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return MappingConfigType::NAME;
    }
}
