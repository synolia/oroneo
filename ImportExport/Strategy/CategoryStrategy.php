<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Synolia\Bundle\OroneoBundle\Repository\CategoryRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Class CategoryStrategy
 * @package   Synolia\Bundle\OroneoBundle\ImportExport\Strategy
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class CategoryStrategy extends LocalizedFallbackValueAwareStrategy
{
    /** @var CategoryRepository $categoryRepository*/
    protected $categoryRepository;

    /** @var ConfigManager $globalConfigManager*/
    protected $globalConfigManager;

    /**
     * @param CategoryRepository $categoryRepository
     */
    public function setCategoryRepository($categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param ConfigManager $globalConfigManager
     */
    public function setGlobalConfigManager(ConfigManager $globalConfigManager)
    {
        $this->globalConfigManager = $globalConfigManager;
    }

    /**
     * Check parent category and assign if it exists.
     *
     * @param Category $entity
     * @return Category
     */
    protected function beforeProcessEntity($entity)
    {
        $itemData = $this->context->getValue('itemData');

        $parentCategoryId = $this->globalConfigManager->get('synolia_oroneo.master_category');
        $parentCategory = $this->categoryRepository->getCategoryById(current($parentCategoryId));

        if (!empty($itemData['parentCategory'])) {
            // Check parent category in context.
            $parentCategory = $this->context->getValue($itemData['parentCategory']['akeneoCategoryCode']);
        }
        if (!$parentCategory && isset($itemData['parentCategory'])) {
            // Check parent category in database. See if it is really necessary here or if it is properly done in updateRelation().
            $parentCategory = $this->categoryRepository->getParentCategoryByAkeneoCategoryCode($itemData['parentCategory']['akeneoCategoryCode']);
        }
        $entity->setParentCategory($parentCategory);

        return parent::beforeProcessEntity($entity);
    }

    /**
     * Write in the related database table to keep tracking imports.
     *
     * @param Category $entity
     * @return Category
     */
    protected function afterProcessEntity($entity)
    {
        // Set the temporary entity in context.
        if ($entity->getAkeneoCategoryCode() && $entity->getAkeneoCategoryCode() != null) {
            $this->context->setValue($entity->getAkeneoCategoryCode(), $entity);
        }

        // Do not allow empty category titles. Add code if nothing in label column.
        if (null === $entity->getDefaultTitle()) {
            $newTitle = new LocalizedFallbackValue();
            $newTitle->setString($entity->getAkeneoCategoryCode());
            $entity->addTitle($newTitle);
        }

        // Return the entity directly to avoid the use of the method setLocalizationKeys.
        // This method seems to remove relations between Categories and their LocalizedFallbackValue.
        return parent::afterProcessEntity($entity);
    }

    /**
     * Checks for the parent category when categories are created
     * @param object $entity
     * @param array  $searchContext
     *
     * @return null|object
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        $existingEntity = parent::findExistingEntity($entity, $searchContext);

        if ($existingEntity == null
            && $entity instanceof Category
        ) {
            $existingEntity = $this->context->getValue($entity->getAkeneoCategoryCode());
        }

        return $existingEntity;
    }

    /**
     * Allows to update titles with the proper locales for categories
     *
     * @param object $entity
     * @param bool   $isFullData
     * @param bool   $isPersistNew
     * @param null   $itemData
     * @param array  $searchContext
     * @param bool   $entityIsRelation
     *
     * @return null|object
     */
    protected function processEntity(
        $entity,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = array(),
        $entityIsRelation = false
    ) {
        if ($entity instanceof LocalizedFallbackValue) {
            $isFullData = true;

            $locale = $entity->getLocalization();
            if ($locale != null && $locale->getId() == null) {
                $entity->setLocalization(parent::findExistingEntity($locale));
            }
        }

        return parent::processEntity($entity, $isFullData, $isPersistNew, $itemData, $searchContext, $entityIsRelation);
    }

    /**
     * @param object $entity
     * @param array $field
     * @throws \Exception
     */
    protected function setLocalizationKeys($entity, array $field)
    {
        return;
    }
}
