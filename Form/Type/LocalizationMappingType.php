<?php

namespace Synolia\Bundle\OroneoBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Synolia\Bundle\OroneoBundle\Manager\OroFieldSelectManager;

/**
 * Class LocalizationMappingType
 * @package Synolia\Bundle\OroneoBundle\Form\Type
 */
class LocalizationMappingType extends AbstractType
{
    const NAME = 'synolia_Oroneo_localization_mapping_type';

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add(
                'akeneoLocalization',
                'text',
                [
                    'empty_data' => null,
                ]
            )
            ->add(
                'oroLocalization',
                'choice',
                [
                    'choices' => $this->oroFieldChoices->getLocalizationChoices(),
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
