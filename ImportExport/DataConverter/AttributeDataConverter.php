<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\DataConverter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter\EntityFieldDataConverter;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Class AttributeDataConverter
 */
class AttributeDataConverter extends EntityFieldDataConverter implements ContextAwareInterface
{
    use DataConverterTrait;

    /** @var ConfigManager $entityConfigManager*/
    protected $entityConfigManager;

    /** @var null|EntityConfigModel */
    protected $productConfigModel;

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
     * AttributeDataConverter constructor.
     *
     * @param ConfigManager $entityConfigManager
     */
    public function __construct(ConfigManager $entityConfigManager)
    {
        $this->entityConfigManager = $entityConfigManager;
        $this->productConfigModel  = $entityConfigManager->getConfigEntityModel(Product::class);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $this->checkMissingFields($importedRecord, $this->context);

        // Define ProductEntity's ID from 'oro_entity_config' table.
        $importedRecord['entity:id'] = $this->productConfigModel->getId();

        // Manage Akeneo field type. It is always under the form 'pim_catalog_TYPE'.
        if (isset($importedRecord['type'])) {
            $type = explode('_', $importedRecord['type']);
            $akeneoTypes = $this->getAkeneoDataTypes();
            if (array_key_exists(end($type), $akeneoTypes)) {
                $importedRecord['type'] = $akeneoTypes[end($type)];
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
        $localeCode = $this->getDefaultLocalization()->getAkeneoLocalization();

        return [
            'code'                   => 'fieldName',
            'type'                   => 'type',
            'label-'.$localeCode     => 'entity.label',
            'useable_as_grid_filter' => 'datagrid.show_filter',
            'sort_order'             => 'view.priority',
            'max_characters'         => 'extend.length',
            'max_file_size'          => 'attachment.maxsize',
        ];
    }

    /**
     * Mapping between Akeneo field type and Oro types.
     * Keys are Akeneo field types, values are Oro's.
     *
     * @return array
     */
    protected function getAkeneoDataTypes()
    {
        return [
            'identifier'       => 'string',
            'text'             => 'string',
            'textarea'         => 'text',
            'metric'           => 'decimal',
            'boolean'          => 'boolean',
            'simpleselect'     => 'enum',
            'number'           => 'decimal',
            'multiselect'      => 'multiEnum',
            'date'             => 'date',
            'image'            => 'image',
            'file'             => 'file',
        ];
    }
}
