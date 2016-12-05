<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Synolia\Bundle\OroneoBundle\Repository\CategoryRepository;

/**
 * Class CategoryStrategy
 * @todo Check is we need to set an owner (see to inject Oro\Bundle\SecurityBundle\SecurityFacade).
 * @todo do not forget to add the eventDispatcher when everything is stable.
 *
 * @see \Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy->importExistingEntity()
 */
class CategoryStrategy extends LocalizedFallbackValueAwareStrategy
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @param CategoryRepository $categoryRepository
     */
    public function setCategoryRepository($categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Check parent category and assign if it exists.
     *
     * @todo Rework has to be done on the itemData array and use good locales dynamically.
     *
     * @param Category $entity
     * @return Category
     */
    protected function beforeProcessEntity($entity)
    {
        $itemData = $this->context->getValue('itemData');

        if (isset($itemData['parentCategory'])) {
            // Check parent category in context.
            $parentCategory = $this->context->getValue($itemData['parentCategory']['akeneoCategoryCode']);
            if (!$parentCategory) {
                // Check parent category in database. See if it is really necessary here or if it is properly donne in updateRelation().
                $parentCategory = $this->categoryRepository->getParentCategoryByAkeneoCategoryCode($itemData['parentCategory']['akeneoCategoryCode']);
            }

            if ($parentCategory) {
                $entity->setParentCategory($parentCategory);
            }
        }

        return parent::beforeProcessEntity($entity);
    }

    /**
     * Write in the related database table to keep tracking imports.
     *
     * @todo Find a better way to store temporary entities to be able to link them between each others.
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

        // Return the entity directly to avoid the use of the method setLocalizationKeys.
        // This method seems to remove relations between Categories and their LocalizedFallbackValue.
        return parent::afterProcessEntity($entity);
    }

    /**
     * @param object $entity
     * @param array $field
     * @throws \Exception
     */
    protected function setLocalizationKeys($entity, array $field)
    {
        //Check with Oro what that method is for as it seems to create bugs
        return;
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
}
