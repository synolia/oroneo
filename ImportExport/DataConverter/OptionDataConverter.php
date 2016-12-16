<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;

/**
 * Class OptionDataConverter
 * @package Synolia\Bundle\OroneoBundle\ImportExport\DataConverter
 */
class OptionDataConverter extends AbstractTableDataConverter implements ContextAwareInterface
{
    use DataConverterTrait;

    /** @var ContextInterface */
    protected $context;

    /**
     * @param ContextInterface $context
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @param array $importedRecord
     * @param bool  $skipNullValues
     *
     * @return array
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $this->checkMissingFields($importedRecord, $this->context);

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        $defaultLocalization = $this->getDefaultLocalization();
        if ($defaultLocalization === null) {
            throw new RuntimeException(
                'There is no default localization set up.'
            );
        }

        $localeCode = $defaultLocalization->getAkeneoLocalization();

        return [
            'code'               => 'id',
            'label-'.$localeCode => 'name',
            'sort_order'         => 'priority',
            'attribute'          => 'attribute',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }
}
