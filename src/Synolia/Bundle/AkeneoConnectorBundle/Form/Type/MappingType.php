<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Class MappingType
 * @package Synolia\Bundle\AkeneoConnectorBundle\Form\Type
 */
class MappingType extends AbstractType
{
    const NAME = 'synolia_akeneoconnector_mapping';
    const AKENEO_FIELD = 'akeneoField';
    const ORO_FIELD = 'oroField';
    const ORO_ENTITY_FIELD = 'oroEntityField';
    const REQUIRED = 'required';
    const TRANSLATABLE = 'translatable';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::AKENEO_FIELD,
                'text',
                [
                    'empty_data' => null,
                    'required' => true,
                ]
            )
            ->add(
                self::ORO_ENTITY_FIELD,
                'text'
            )
            ->add(
                self::REQUIRED,
                'checkbox',
                [
                    'required' => false,
                ]
            )
            ->add(
                self::TRANSLATABLE,
                'checkbox',
                [
                    'required' => false,
                ]
            );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
