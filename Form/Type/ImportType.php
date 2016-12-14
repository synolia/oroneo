<?php

namespace Synolia\Bundle\OroneoBundle\Form\Type;

use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Oro\Bundle\ImportExportBundle\Form\Type\ImportType as BaseImportType;

/**
 * Class ImportType
 */
class ImportType extends BaseImportType
{
    /**
     * @var ProcessorRegistry
     */
    protected $processorRegistry;

    /**
     * @param ProcessorRegistry $processorRegistry
     */
    public function __construct(ProcessorRegistry $processorRegistry)
    {
        $this->processorRegistry = $processorRegistry;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'processorAlias',
            ChoiceType::class,
            [
                'label'    => 'synolia.oroneo.import_page.processor.choice',
                'expanded' => false,
                'multiple' => false,
                'choices'  => $options['processorsChoices'],
            ]
        );

        $builder->add(
            'file',
            FileType::class,
            [
                'constraints' => [
                    new File(
                        [
                            'mimeTypes' => ['text/plain', 'text/csv', 'application/zip'],
                            'mimeTypesMessage' => 'This file type is not allowed.',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'validateBtn',
            SubmitType::class,
            [
                'label' => 'synolia.oroneo.import_page.validation.btn',
                'attr' => [
                    'class' => 'btn btn-success main-group pull-right',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'synolia_oroneo_import_form';
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\ImportExportBundle\Form\Model\ImportData',
            ]
        );
        $resolver->setRequired(['processorsChoices']);
        $resolver->setAllowedTypes(['processorsChoices' => 'array']);
    }
}
