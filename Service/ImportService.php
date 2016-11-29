<?php

namespace Synolia\Bundle\OroneoBundle\Service;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ImportService
 */
class ImportService
{
    const CATEGORY_PROCESSOR          = 'synolia.import.processor.category';
    const ATTRIBUTE_PROCESSOR         = 'synolia.import.processor.attribute';
    const OPTION_PROCESSOR            = 'synolia.import.processor.option';
    const PRODUCT_PROCESSOR           = 'synolia.import.processor.product';
    const PRODUCT_FILE_PROCESSOR      = 'synolia.import.processor.product_file';
    const ATTRIBUTE_VALIDATION_JOB    = 'synolia_akeneo_attribute_import_validation_from_csv';
    const ATTRIBUTE_JOB               = 'synolia_akeneo_attribute_import_from_csv';
    const PRODUCT_FILE_VALIDATION_JOB = 'synolia_akeneo_product_file_import_validation_from_zip';
    const PRODUCT_FILE_JOB            = 'synolia_akeneo_product_file_import_from_zip';

    /**
     * @var TranslatorInterface $translator
     */
    protected $translator;

    /**
     * ImportService constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator   = $translator;
    }

    /**
     * @return array
     */
    public function getProcessorsChoices()
    {
        return [
            self::CATEGORY_PROCESSOR     => $this->translator->trans('synolia.oroneo.category.label'),
            self::ATTRIBUTE_PROCESSOR    => $this->translator->trans('synolia.oroneo.attribute.label'),
            self::OPTION_PROCESSOR       => $this->translator->trans('synolia.oroneo.option.label'),
            self::PRODUCT_PROCESSOR      => $this->translator->trans('synolia.oroneo.product.label'),
            self::PRODUCT_FILE_PROCESSOR => $this->translator->trans('synolia.oroneo.product_file.label'),
        ];
    }
}
