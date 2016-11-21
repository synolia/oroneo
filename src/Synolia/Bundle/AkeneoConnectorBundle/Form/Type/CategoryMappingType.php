<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Synolia\Bundle\AkeneoConnectorBundle\Service\OroFieldSelectService;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * Class CategoryMappingType
 * @package Synolia\Bundle\AkeneoConnectorBundle\Form\Type
 */
class CategoryMappingType extends MappingType
{
    const NAME = 'synolia_akeneoconnector_category_mapping_type';

    /**
     * @var OroFieldSelectService $oroFieldChoices
     */
    protected $oroFieldChoices;

    /**
     * MappingType constructor.
     *
     * @param OroFieldSelectService $oroFieldChoices
     */
    public function __construct(OroFieldSelectService $oroFieldChoices)
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
                'choices' => $this->oroFieldChoices->getChoices(Category::class),
            ]
        );
    }
}
