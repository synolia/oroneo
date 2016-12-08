<?php

namespace Synolia\Bundle\OroneoBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Synolia\Bundle\OroneoBundle\Manager\OroFieldSelectManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class MasterCategoryType
 * @package Synolia\Bundle\OroneoBundle\Form\Type
 */
class MasterCategoryType extends AbstractType
{
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
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'masterCategory',
            ChoiceType::class,
            [
                'choices' => $this->oroFieldChoices->getCategoriesChoices(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'synolia_oroneo_master_category_type';
    }
}
