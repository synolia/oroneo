<?php

namespace Synolia\Bundle\OroneoBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Synolia\Bundle\OroneoBundle\Manager\OroFieldSelectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class CategoryMappingType
 * @package Synolia\Bundle\OroneoBundle\Form\Type
 */
class CategoryMappingType extends MappingType
{
    const NAME = 'synolia_oroneo_category_mapping_type';

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
            ChoiceType::class,
            [
                'choices' => $this->oroFieldChoices->getChoices(Category::class),
            ]
        );
    }
}
