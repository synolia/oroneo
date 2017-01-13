<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use Synolia\Bundle\OroneoBundle\Manager\MappingManager;

/**
 * Class OptionDataConverter
 * @package Synolia\Bundle\OroneoBundle\ImportExport\DataConverter
 */
class OptionDataConverter extends AbstractTableDataConverter implements ContextAwareInterface
{
    /** @var MappingManager */
    protected $mappingManager;

    /** @var ContextInterface */
    protected $context;

    /**
     * @param MappingManager $mappingManager
     */
    public function setMappingManager(MappingManager $mappingManager)
    {
        $this->mappingManager = $mappingManager;
    }

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
        $this->mappingManager->checkMissingFields($importedRecord, $this->context);

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return $this->mappingManager->getMappings();
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }
}
