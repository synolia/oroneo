<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\DataConverter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter\EntityFieldDataConverter;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Synolia\Bundle\OroneoBundle\Manager\MappingManager;

/**
 * Class AttributeDataConverter
 * @package   Synolia\Bundle\OroneoBundle\ImportExport\DataConverter
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
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
    protected $productConfigModel = null;

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
        $importedRecord['entity:id'] = $this->getProductConfigModel()->getId();

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
        $mappings = $this->mappingManager->getMappings();

        $labelKey = array_search('entity.label', $mappings);

        if ($labelKey !== false) {
            $mappings[$labelKey.'-'.$this->mappingManager->getDefaultLocalization()->getAkeneoLocalization()] = 'entity.label';
        }

        return $mappings;
    }

    /**
     * @return EntityConfigModel
     */
    protected function getProductConfigModel()
    {
        if ($this->productConfigModel === null) {
            $this->productConfigModel = $this->entityConfigManager->getConfigEntityModel(Product::class);
        }

        return $this->productConfigModel;
    }
}
