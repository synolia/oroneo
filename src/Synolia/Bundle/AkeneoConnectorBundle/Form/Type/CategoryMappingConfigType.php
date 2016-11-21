<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CategoryMappingConfigType
 * @package Synolia\Bundle\AkeneoConnectorBundle\Form\Type
 */
class CategoryMappingConfigType extends AbstractType
{
    const NAME = 'synolia_akeneoconnector_category_mapping';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => CategoryMappingType::NAME,
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
