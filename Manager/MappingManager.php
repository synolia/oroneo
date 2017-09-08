<?php

namespace Synolia\Bundle\OroneoBundle\Manager;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Synolia\Bundle\OroneoBundle\SystemConfig\MappingConfig;
use Synolia\Bundle\OroneoBundle\SystemConfig\MappingLocalization;

/**
 * Class MappingManager
 * @package   Synolia\Bundle\OroneoBundle\Manager
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class MappingManager
{
    const ORONEO_FIELD = 'oroneo';

    /** @var string */
    protected $className;

    /** @var ConfigManager */
    protected $configManager;

    /** @var MappingConfig[] */
    protected $fields = null;

    /** @var array */
    protected $missingFields = [];

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function getMissingFields($fields)
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
    public function checkMissingFields($fields, ContextInterface $context)
    {
        if (!is_array($this->missingFields)) {
            $this->missingFields = $this->getMissingFields($fields);
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
    public function getMappings()
    {
        $fieldMappings        = $this->getFieldMappings();
        $localizationMappings = $this->getLocalizationMappings();

        $mappings = [];
        /** @var MappingConfig[] $fieldMappings */
        foreach ($fieldMappings as $fieldMapping) {
            if (empty($fieldMapping->getOroField()) || $fieldMapping->getOroField() == self::ORONEO_FIELD) {
                $mappings[$fieldMapping->getAkeneoField()] = $fieldMapping->getOroEntityField();
            } elseif ($fieldMapping->isTranslatable()) {
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
    public function getLocalizationMappings()
    {
        return $this->configManager->get('synolia_oroneo.localization_mapping');
    }

    /**
     * @return MappingLocalization|null
     */
    public function getDefaultLocalization()
    {
        $mappings = $this->getLocalizationMappings();
        $result   = null;
        foreach ($mappings as $mapping) {
            if ($mapping->getOroLocalization() == 'default') {
                $result = $mapping;
                break;
            }
        }

        if ($result === null) {
            throw new RuntimeException(
                'There is no default localization set up.'
            );
        }

        return $result;
    }

    /**
     * @return MappingConfig[]
     * @throws \Exception
     */
    public function getFieldMappings()
    {
        if (!is_array($this->fields)) {
            $this->fields = $this->configManager->get('synolia_oroneo.'.$this->className.'_mapping');
        }

        return $this->fields;
    }
}
