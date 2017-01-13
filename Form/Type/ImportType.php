<?php

namespace Synolia\Bundle\OroneoBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
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
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->remove('file');
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
            'isManualImport',
            CheckboxType::class,
            [
                'label'    => 'synolia.oroneo.import_page.import_type.checkbox',
                'required' => false,
                'mapped'   => false,
            ]
        );

        $builder->add(
            'file',
            FileType::class,
            [
                'required'    => false,
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
