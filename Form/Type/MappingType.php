<?php

namespace Synolia\Bundle\OroneoBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Synolia\Bundle\OroneoBundle\Manager\OroFieldSelectManager;

/**
 * Class MappingType
 * @package   Synolia\Bundle\OroneoBundle\Form\Type
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class MappingType extends AbstractType
{
    const NAME             = 'synolia_oroneo_mapping';
    const AKENEO_FIELD     = 'akeneoField';
    const ORO_FIELD        = 'oroField';
    const ORO_ENTITY_FIELD = 'oroEntityField';
    const REQUIRED         = 'required';
    const TRANSLATABLE     = 'translatable';

    /** @var string $name */
    protected $name;

    /** @var string $className */
    protected $className;

    /** @var OroFieldSelectManager $oroFieldChoices */
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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::AKENEO_FIELD,
                TextType::class,
                [
                    'empty_data' => null,
                    'required'   => true,
                ]
            )
            ->add(
                self::ORO_FIELD,
                ChoiceType::class,
                [
                    'choices' => $this->getOroFieldChoices($this->className),
                ]
            )
            ->add(
                self::ORO_ENTITY_FIELD,
                TextType::class
            )
            ->add(
                self::REQUIRED,
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                self::TRANSLATABLE,
                CheckboxType::class,
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
        if ($this->name === null) {
            return self::NAME;
        }

        return $this->name;
    }

    /**
     * @param string $className
     *
     * @return array
     */
    public function getOroFieldChoices($className)
    {
        return [
            'Special values' => [
                ''       => 'custom',
                'oroneo' => 'Oroneo',
            ],
            'Fields'       => $this->oroFieldChoices->getChoices($className),
        ];
    }
}
