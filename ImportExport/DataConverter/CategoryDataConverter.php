<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;

/**
 * Class CategoryDataConverter
 */
class CategoryDataConverter extends LocalizedFallbackValueAwareDataConverter implements ContextAwareInterface
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
        return $this->getMappings();
    }
}
