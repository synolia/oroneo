<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Common\Util\ClassUtils;
use \OroB2B\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy as Strategy;

/**
 * Class ProductStrategy
 */
class ProductStrategy extends Strategy
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityManager */
    protected $entityManager;

    /** @var BusinessUnitRepository */
    protected $businessUnitRepo;

    /** @var ProductUnitRepository */
    protected $productUnitRepo;

    /** @var CategoryRepository */
    protected $categoryRepo;

    /** @var FieldConfigModel[][] */
    protected $fields;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager    = $entityManager;

        $this->businessUnitRepo = $entityManager->getRepository('OroOrganizationBundle:BusinessUnit');
        $this->productUnitRepo  = $entityManager->getRepository('OroB2BProductBundle:ProductUnit');
        $this->categoryRepo     = $entityManager->getRepository('OroB2BCatalogBundle:Category');
    }

    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        if (!$entity->getId()) {
            //Only for new products, we set some mandatory parameters
            $entity->setStatus('enabled');

            if (!$entity->getOwner()) {
                $owner = $this->businessUnitRepo->find(1);
                $entity->setOwner($owner);
            }

            if (!$entity->getPrimaryUnitPrecision()) {
                $unit = $this->productUnitRepo->find('item');
                $precision = new ProductUnitPrecision();
                $precision->setUnit($unit);
                $precision->setPrecision(1);

                $entity->setPrimaryUnitPrecision($precision);
            }

            if (!$entity->getInventoryStatus()) {
                $provider = $this->configManager->getProvider('enum');
                $config = $provider->getConfig(Product::class, 'inventory_status');
                $class  = ExtendHelper::buildEnumValueClassName($config->get('enum_code'));

                $inventoryStatus = $this->databaseHelper->find($class, Product::INVENTORY_STATUS_IN_STOCK);
                $entity->setInventoryStatus($inventoryStatus);
            }
        }

        $currentCategory = $this->categoryRepo->findOneByProductSku($entity->getSku());
        $categoryCode    = $this->context->getValue('itemData')['categories'];

        if (!$currentCategory || $currentCategory->getAkeneoCategoryCode() != $categoryCode) {
            $category = $this->categoryRepo->findOneBy(['akeneoCategoryCode' => $categoryCode]);

            if (!$category) {
                $this->context->incrementErrorEntriesCount();
                $this->strategyHelper->addValidationErrors(
                    [
                        $this->translator->trans('synolia.oroneo.import.product.error.category_not_found'),
                    ],
                    $this->context
                );

                return null;
            }

            $category->addProduct($entity);

            $this->entityManager->persist($category);
        }

        $metadata  = $this->doctrineHelper->getEntityMetadata($this->localizedFallbackValueClass);
        $localValueRelations = [];
        foreach ($metadata->getAssociationMappings() as $name => $mapping) {
            if ($metadata->isAssociationInverseSide($name) && $metadata->isCollectionValuedAssociation($name)) {
                $localValueRelations[] = $name;
            }
        }

        $entityFields = $this->fieldHelper->getRelations($this->entityName);
        foreach ($entityFields as $field) {
            if ($this->isLocalizedFallbackValue($field)) {
                $this->removeNotInitializedEntities($entity, $field, $localValueRelations);
            }
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * Clear not initialized entities that might remain in localized entity because of recursive relations
     *
     * @param $entity
     * @param array $field
     * @param array $relations
     */
    protected function removeNotInitializedEntities($entity, array $field, array $relations)
    {
        /** @var Collection|LocalizedFallbackValue[] $localizedFallbackValues */
        $localizedFallbackValues = $this->fieldHelper->getObjectValue($entity, $field['name']);
        foreach ($localizedFallbackValues as $value) {
            foreach ($relations as $relation) {
                /** @var Collection $collection */
                $collection = $this->fieldHelper->getObjectValue($value, $relation);
                if ($collection) {
                    foreach ($collection as $key => $element) {
                        $uow = $this->doctrineHelper->getEntityManager($element)->getUnitOfWork();
                        if ($uow->getEntityState($element, UnitOfWork::STATE_DETACHED) === UnitOfWork::STATE_DETACHED) {
                            $collection->remove($key);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $field
     * {@inheritdoc}
     */
    protected function importExistingEntity($entity, $existingEntity, $itemData = null, array $excludedFields = [])
    {
        if (ClassUtils::getClass($entity) === $this->localizedFallbackValueClass) {
            $metadata = $this->doctrineHelper->getEntityMetadata($this->localizedFallbackValueClass);
            foreach ($metadata->getAssociationMappings() as $name => $mapping) {
                if ($metadata->isAssociationInverseSide($name) && $metadata->isCollectionValuedAssociation($name)) {
                    // exclude all *-to-many relations from import
                    $excludedFields[] = $name;
                }
            }
        }

        parent::importExistingEntity($entity, $existingEntity, $itemData, $excludedFields);
    }
}
