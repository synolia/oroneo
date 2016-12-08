<?php

namespace Synolia\Bundle\OroneoBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Synolia\Bundle\OroneoBundle\Manager\OroFieldSelectManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class LocalizationMappingType
 * @package Synolia\Bundle\OroneoBundle\Form\Type
 */
class LocalizationMappingType extends AbstractType
{
    const NAME = 'synolia_oroneo_localization_mapping_type';

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
                TextType::class,
                [
                    'empty_data' => null,
                ]
            )
            ->add(
                'oroLocalization',
                ChoiceType::class,
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
