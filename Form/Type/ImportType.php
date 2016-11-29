<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\Form\Type;

use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
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
            'choice',
            [
                'label'    => 'synolia.akeneo_connector.import_page.processor.choice',
                'expanded' => false,
                'multiple' => false,
                'choices'  => $options['processorsChoices'],
            ]
        );

        $builder->add(
            'file',
            'file',
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
            'submit',
            [
                'label' => 'synolia.akeneo_connector.import_page.validation.btn',
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
        return 'akeneo_import_form';
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
