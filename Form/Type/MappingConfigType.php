<?php

namespace Synolia\Bundle\OroneoBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

/**
 * Class MappingConfigType
 * @package Synolia\Bundle\OroneoBundle\Form\Type
 */
class MappingConfigType extends AbstractType
{
    const NAME = 'synolia_oroneo_mapping_config';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => MappingType::NAME,
            'options' => [
                'data_class' => 'Synolia\Bundle\OroneoBundle\SystemConfig\MappingConfig',
            ],
            'allow_add_after' => false,
            'show_form_when_empty' => true,
            'allow_add' => true,
            'mapped' => true,
            'label' => false,
            'error_bubbling' => false,
            'handle_primary' => false,
            'required' => false,
            'render_as_widget' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['render_as_widget'] = $options['render_as_widget'];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
