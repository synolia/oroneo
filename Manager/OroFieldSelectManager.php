<?php

namespace Synolia\Bundle\OroneoBundle\Manager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

/**
 * Class OroFieldSelectManager
 * @package Synolia\Bundle\OroneoBundle\Manager
 */
class OroFieldSelectManager
{
    /** @var ConfigManager $configManager */
    protected $configManager;

    /** @var LocalizationHelper $localizationHelper */
    protected $localizationHelper;

    /**
     * OroFieldSelectManager constructor.
     *
     * @param ConfigManager      $configManager
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(ConfigManager $configManager, LocalizationHelper $localizationHelper)
    {
        $this->configManager      = $configManager;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param string $className
     *
     * @return array
     */
    public function getChoices($className)
    {
        $choices = [];
        $product  = $this->configManager->getConfigEntityModel($className);
        foreach ($product->getFields() as $field) {
            $choices[$field->getFieldName()] = $field->getFieldName();
        }

        if (in_array('id', $choices)) {
            unset($choices['id']);
        }

        return $choices;
    }

    /**
     * Return array of Localizations.
     *
     * @return array
     */
    public function getLocalizationChoices()
    {
        $localizations = $this->localizationHelper->getAll();
        $choices       = [
            'default' => 'synolia.oroneo.configuration_page.mapping.localization.default.label',
        ];

        foreach ($localizations as $localization) {
            $choices[$localization->getName()] = $localization->getName();
        }

        return $choices;
    }
}
