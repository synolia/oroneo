<?php

namespace Synolia\Bundle\OroneoBundle\Manager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

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

    /** @var CategoryRepository $categoryRepository */
    protected $categoryRepository;

    /**
     * OroFieldSelectManager constructor.
     *
     * @param ConfigManager      $configManager
     * @param LocalizationHelper $localizationHelper
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        ConfigManager $configManager,
        LocalizationHelper $localizationHelper,
        CategoryRepository $categoryRepository
    ) {
        $this->configManager      = $configManager;
        $this->localizationHelper = $localizationHelper;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param string $className
     *
     * @return array
     */
    public function getChoices($className)
    {
        $choices     = [];
        $configModel = $this->configManager->getConfigEntityModel($className);

        if (!$configModel) {
            return [];
        }

        foreach ($configModel->getFields() as $field) {
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
        $localizations = $this->localizationHelper->getLocalizations();
        $choices       = [
            'default' => 'synolia.oroneo.configuration_page.mapping.localization.default.label',
        ];

        foreach ($localizations as $localization) {
            $choices[$localization->getName()] = $localization->getName();
        }

        return $choices;
    }

    /**
     * Return array of Categories.
     *
     * @return array
     */
    public function getCategoriesChoices()
    {
        $rootCategory = $this->categoryRepository->getMasterCatalogRoot();

        $children = $this->categoryRepository->getAllChildCategories($rootCategory);

        $select[$rootCategory->getId()] = $rootCategory->getDefaultTitle()->getString();

        foreach ($children as $child) {
            $space = str_repeat('--', $child->getLevel());
            $select[$child->getId()] = $space.' '.$child->getDefaultTitle()->getString();

        }

        return $select;
    }
}
