<?php

namespace Synolia\Bundle\OroneoBundle\Manager;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Class OroFieldSelectManager
 * @package   Synolia\Bundle\OroneoBundle\Manager
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class OroFieldSelectManager
{
    /** @var ConfigManager $configManager */
    protected $configManager;

    /** @var LocalizationHelper $localizationHelper */
    protected $localizationHelper;

    /** @var CategoryRepository $categoryRepository */
    protected $categoryRepository;

    /** @var ManagerRegistry $managerRegistry */
    protected $managerRegistry;

    /**
     * OroFieldSelectManager constructor.
     *
     * @param ConfigManager      $configManager
     * @param LocalizationHelper $localizationHelper
     * @param CategoryRepository $categoryRepository
     * @param ManagerRegistry    $managerRegistry
     */
    public function __construct(
        ConfigManager $configManager,
        LocalizationHelper $localizationHelper,
        CategoryRepository $categoryRepository,
        ManagerRegistry $managerRegistry
    ) {
        $this->configManager      = $configManager;
        $this->localizationHelper = $localizationHelper;
        $this->categoryRepository = $categoryRepository;
        $this->managerRegistry    = $managerRegistry;
    }

    /**
     * @param string $className
     *
     * @return array
     * @throws \Exception
     */
    public function getChoices($className)
    {
        if (!$className) {
            throw new \Exception('A class name is needed to retrive its choices');
        }

        $choices     = [];
        $configModel = $this->configManager->getConfigEntityModel($className);

        if (!$configModel) {
            $manager  = $this->managerRegistry->getManagerForClass($className);
            /** @var ClassMetadata $metadata */
            $metadata = $manager->getClassMetadata($className);

            $fields   = array_merge($metadata->getFieldNames(), $metadata->getAssociationNames());
            $choices  = array_combine($fields, $fields);
        } else {
            $provider = $this->configManager->getProvider('extend');

            foreach ($configModel->getFields() as $field) {
                $config = $provider->getConfig($field->getEntity()->getClassName(), $field->getFieldName());
                if ($config->get('origin') != 'Akeneo') {
                    $choices[$field->getFieldName()] = $field->getFieldName();
                }
            }
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
            /** @var Category $child */
            if (null !== $child->getDefaultTitle()) {
                $space = str_repeat('--', $child->getLevel());
                $select[$child->getId()] = $space.' '.$child->getDefaultTitle()->getString();
            }
        }

        return $select;
    }
}
