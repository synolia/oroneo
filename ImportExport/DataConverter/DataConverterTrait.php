<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\DataConverter;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Synolia\Bundle\OroneoBundle\SystemConfig\MappingConfig;
use Synolia\Bundle\OroneoBundle\SystemConfig\MappingLocalization;

/**
 * Class DataConverterTrait
 * @package Synolia\Bundle\OroneoBundle\ImportExport\DataConverter
 */
trait DataConverterTrait
{
    /** @var  ManagerRegistry */
    protected $managerRegistry;

    /** @var  string */
    protected $className;

    /** @var  ConfigManager */
    protected $globalConfigManager;

    /** @var  MappingConfig[] */
    protected $fields = null;

    /** @var bool */
    protected $firstRun = true;

    protected $missingFields = [];

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function setManagerRegistry(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @param ConfigManager $globalConfigManager
     */
    public function setGlobalConfigManager(ConfigManager $globalConfigManager)
    {
        $this->globalConfigManager = $globalConfigManager;
    }

    /**
     * @param array  $fields
     *
     * @return array
     */
    protected function getMissingFields($fields)
    {
        $fieldMappings       = $this->getFieldMappings();
        $defaultLocalization = $this->getDefaultLocalization();
        $missingColumns      = [];

        foreach ($fieldMappings as $fieldMapping) {
            if ($fieldMapping->isRequired()) {
                $fieldName = $fieldMapping->getAkeneoField();
                if ($fieldMapping->isTranslatable()) {
                    $fieldName .= '-'.$defaultLocalization->getAkeneoLocalization();
                }

                if (!isset($fields[$fieldName])) {
                    $missingColumns[] = $fieldMapping->getAkeneoField();
                }
            }
        }

        return $missingColumns;
    }

    /**
     * @param array            $fields
     * @param ContextInterface $context
     *
     * @throws InvalidItemException
     */
    protected function checkMissingFields($fields, ContextInterface $context)
    {
        if ($this->firstRun) {
            $this->missingFields = $this->getMissingFields($fields);
            $this->firstRun = false;
            if (!empty($this->missingFields)) {
                $context->addError('Missing required columns: '.implode(', ', $this->missingFields));
            }
        }

        if (!empty($this->missingFields)) {
            $context->incrementErrorEntriesCount();
            throw new InvalidItemException('Missing required columns: '.implode(', ', $this->missingFields), $this->missingFields);
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getMappings()
    {
        $fieldMappings        = $this->getFieldMappings();
        $localizationMappings = $this->getLocalizationMappings();

        $mappings = [];
        /** @var MappingConfig[] $fieldMappings */
        foreach ($fieldMappings as $fieldMapping) {
            if ($fieldMapping->isTranslatable()) {
                foreach ($localizationMappings as $localizationMapping) {
                    $mappings[$fieldMapping->getAkeneoField().'-'.$localizationMapping->getAkeneoLocalization()] =
                        $fieldMapping->getOroField().':'.$localizationMapping->getOroLocalization().':'.$fieldMapping->getOroEntityField();
                }
            } elseif (!empty($fieldMapping->getOroEntityField())) {
                $mappings[$fieldMapping->getAkeneoField()] = $fieldMapping->getOroField().':'.$fieldMapping->getOroEntityField();
            } else {
                $mappings[$fieldMapping->getAkeneoField()] = $fieldMapping->getOroField();
            }
        }

        return $mappings;
    }

    /**
     * @return MappingLocalization[]
     */
    protected function getLocalizationMappings()
    {
        return $this->globalConfigManager->get('synolia_oroneo.localization_mapping');
    }

    /**
     * @return MappingLocalization|null
     */
    protected function getDefaultLocalization()
    {
        $mappings = $this->getLocalizationMappings();
        $result   = null;
        foreach ($mappings as $mapping) {
            if ($mapping->getOroLocalization() == 'default') {
                $result = $mapping;
                break;
            }
        }

        return $result;
    }

    /**
     * @return MappingConfig[]
     * @throws \Exception
     */
    protected function getFieldMappings()
    {
        if (!is_array($this->fields)) {
            $this->fields = $this->globalConfigManager->get('synolia_oroneo.'.$this->className.'_mapping');
        }

        return $this->fields;
    }
}
