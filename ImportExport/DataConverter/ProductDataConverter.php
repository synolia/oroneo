<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\DataConverter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager as GlobalConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\DataConverter\ProductDataConverter as DataConverter;
use Synolia\Bundle\OroneoBundle\Manager\MappingManager;

/**
 * Class ProductDataConverter
 */
class ProductDataConverter extends DataConverter implements ContextAwareInterface
{
    /** @var ConfigManager  */
    protected $configManager;

    /** @var  FieldConfigModel[] */
    protected $multiSelectFields = [];

    /** @var MappingManager */
    protected $mappingManager;

    /** @var GlobalConfigManager */
    protected $globalConfigManager;

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
     * @param GlobalConfigManager $globalConfigManager
     */
    public function setGlobalConfigManager(GlobalConfigManager $globalConfigManager)
    {
        $this->globalConfigManager = $globalConfigManager;
    }

    /**
     * @param ContextInterface $context
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $this->mappingManager->checkMissingFields($importedRecord, $this->context);

        $channel       = '-'.$this->globalConfigManager->get('synolia_oroneo.product_channel');
        $channelLength = strlen($channel);

        foreach ($importedRecord as $key => $value) {
            if (substr($key, -$channelLength) == $channel) {
                $importedRecord[str_replace($channel, '', $key)] = $importedRecord[$key];
                unset($importedRecord[$key]);
            }
        }

        $record = parent::convertToImportFormat($importedRecord, true);

        foreach ($this->multiSelectFields as $multiSelectField) {
            if (array_key_exists($multiSelectField->getFieldName(), $record)) {
                $fieldName = $multiSelectField->getFieldName();
                $values    = explode(',', $record[$fieldName]);

                $record[$fieldName] = [];
                foreach ($values as $value) {
                    $record[$fieldName][] = ['id' => $value];
                }
            }
        }

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        $product  = $this->configManager->getConfigEntityModel(Product::class);
        $provider = $this->configManager->getProvider('extend');
        $fields   = $product->getFields();

        $mappings = $this->mappingManager->getMappings();
        $locales  = $this->mappingManager->getLocalizationMappings();

        foreach ($fields as $field) {
            $mappingName = $field->getFieldName();
            $config      = $provider->getConfig(Product::class, $mappingName);

            if ($config->get('owner') != ExtendScope::OWNER_CUSTOM && $config->get('origin') != 'Akeneo') {
                continue;
            }

            if ($field->getType() == RelationType::MANY_TO_MANY) {
                if ($config->get('target_entity') == LocalizedFallbackValue::class) {
                    $targetColumn = $config->has('target_column') ? $config->get('target_column') : 'string';

                    foreach ($locales as $locale) {
                        $mappings[$mappingName.'-'.$locale->getAkeneoLocalization()] = $mappingName.':'.$locale->getOroLocalization().':'.$targetColumn;
                    }
                }
            } elseif ($field->getType() == 'enum') {
                $mappings[$mappingName] = $mappingName.':id';
            } elseif ($field->getType() == 'multiEnum') {
                $this->multiSelectFields[] = $field;
            }
        }

        return $mappings;
    }
}
