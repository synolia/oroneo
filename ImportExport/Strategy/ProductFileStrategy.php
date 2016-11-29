<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Doctrine\ORM\UnitOfWork;
use \OroB2B\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy as Strategy;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

/**
 * Class ProductFileStrategy
 */
class ProductFileStrategy extends Strategy
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
        $currentCategory = $this->categoryRepo->findOneByProductSku($entity->getSku());
        $categoryCode    = $this->context->getValue('itemData')['categories'];

        if (!$currentCategory || $currentCategory->getAkeneoCategoryCode() != $categoryCode) {
            $category = $this->categoryRepo->findOneBy(['akeneoCategoryCode' => $categoryCode]);
            $category->addProduct($entity);

            $this->entityManager->persist($category);
        }

         $this->setProductFiles($entity);

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

    /** {@inheritdoc} */
    protected function beforeProcessEntity($entity)
    {
        $existingEntity = $this->findExistingEntity($entity);
        if (!$existingEntity) {
            return null;
        }

        return parent::beforeProcessEntity($entity);
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
     * @param Product $entity
     */
    protected function setProductFiles($entity)
    {
        $itemData = $this->context->getValue('itemData');
        $fields   = array_merge($this->getProductFieldsByType('image'), $this->getProductFieldsByType('file'));
        $configuration = $this->context->getConfiguration();
        $filePath = substr($configuration['filePath'], 0, -4);

        foreach ($fields as $field) {
            /** @var FieldConfigModel $field */
            $fieldName = $field->getFieldName();
            if (isset($itemData[$fieldName])) {
                $file = null;

                if (!empty($itemData[$fieldName])) {
                    $componentFile = new ComponentFile($filePath.'/'.$itemData[$fieldName]);
                    $file          = $this->fieldHelper->getObjectValue($entity, $fieldName);

                    if ($file == null
                        || md5_file($file->getFile()->getPathname()) != md5_file($componentFile->getPathname())
                    ) {
                        $file = new File();
                        $file->setFile($componentFile);
                    }
                }
                $this->fieldHelper->setObjectValue($entity, $fieldName, $file);
            }
        }
    }

    /**
     * Get the product fields of a given type
     *
     * @param string $type
     *
     * @return FieldConfigModel[]
     */
    protected function getProductFieldsByType($type)
    {
        if (!isset($this->fields[$type])) {
            $product             = $this->configManager->getConfigEntityModel(Product::class);
            $this->fields[$type] = $product->getFields(function ($field) use ($type) {
                return $field->getType() == $type;
            })->toArray();
        }

        return $this->fields[$type];
    }
}
