<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\DataConverter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter\EntityFieldDataConverter;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ProductBundle\Entity\Product;
use Synolia\Bundle\OroneoBundle\Manager\MappingManager;

/**
 * Class AttributeDataConverter
 */
class AttributeDataConverter extends EntityFieldDataConverter implements ContextAwareInterface
{
    /**
     * Mapping between Akeneo field type and Oro types.
     * Keys are Akeneo field types, values are Oro's.
     *
     * @var string[]
     */
    protected $attributeMapping;

    /** @var ConfigManager */
    protected $entityConfigManager;

    /** @var null|EntityConfigModel */
    protected $productConfigModel;

    /** @var MappingManager */
    protected $mappingManager;

    /** @var ContextInterface */
    protected $context;

    /**
     * AttributeDataConverter constructor.
     *
     * @param ConfigManager $entityConfigManager
     * @param string[]      $attributeMapping
     */
    public function __construct(ConfigManager $entityConfigManager, array $attributeMapping)
    {
        $this->entityConfigManager = $entityConfigManager;
        $this->productConfigModel  = $entityConfigManager->getConfigEntityModel(Product::class);
        $this->attributeMapping    = $attributeMapping;
    }

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
     * {@inheritDoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $this->mappingManager->checkMissingFields($importedRecord, $this->context);

        // Define ProductEntity's ID from 'oro_entity_config' table.
        $importedRecord['entity:id'] = $this->productConfigModel->getId();

        // Manage Akeneo field type. It is always under the form 'pim_catalog_TYPE'.
        if (isset($importedRecord['type'])) {
            if (array_key_exists($importedRecord['type'], $this->attributeMapping)) {
                $importedRecord['type'] = $this->attributeMapping[$importedRecord['type']];
            }
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return $this->mappingManager->getMappings();
    }
}
