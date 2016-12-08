<?php

namespace Synolia\Bundle\OroneoBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ProductMappingConfigType
 * @package Synolia\Bundle\OroneoBundle\Form\Type
 */
class ProductMappingConfigType extends AbstractType
{
    const NAME = 'synolia_oroneo_product_mapping';

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
