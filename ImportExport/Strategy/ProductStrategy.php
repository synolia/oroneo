<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use \Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy as Strategy;
use Symfony\Bridge\Monolog\Logger;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Class ProductStrategy
 * @package   Synolia\Bundle\OroneoBundle\ImportExport\Strategy
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class ProductStrategy extends Strategy
{
    /** @var EntityConfigManager */
    protected $entityConfigManager;

    /** @var BusinessUnitRepository */
    protected $businessUnitRepo;

    /** @var ProductUnitRepository */
    protected $productUnitRepo;

    /** @var CategoryRepository */
    protected $categoryRepo;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var Logger */
    protected $logger;

    /** @var ConfigManager $configManager */
    protected $configManager;

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
     * @param EntityConfigManager $entityConfigManager
     */
    public function setEntityConfigManager(EntityConfigManager $entityConfigManager)
    {
        $this->entityConfigManager = $entityConfigManager;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        $itemData = $this->context->getValue('itemData');

        if (isset($itemData['status'])) {
            $entity->setStatus($itemData['status'] ? Product::STATUS_ENABLED : Product::STATUS_DISABLED);
        }

        if (!$entity->getId()) {
            //Only for new products, we set some mandatory parameters

            if (!isset($itemData['status'])) {
                $entity->setStatus(Product::STATUS_ENABLED);
            }

            if (!$entity->getOwner()) {
                $defaultBusinessUnit = $this->configManager->get('synolia_oroneo.default_business_unit');
                $owner = $this->getBusinessUnitRepository()->find($defaultBusinessUnit);
                $entity->setOwner($owner);
            }

            if (!$entity->getPrimaryUnitPrecision()) {
                $defaultProductUnit = $this->configManager->get('oro_product.default_unit');
                $unit = $this->getProductUnitRepository()->find($defaultProductUnit);
                $precision = new ProductUnitPrecision();
                $precision->setUnit($unit);
                $precision->setPrecision(1);

                $entity->setPrimaryUnitPrecision($precision);
            }

            if (!$entity->getInventoryStatus()) {
                $provider = $this->entityConfigManager->getProvider('enum');
                $config = $provider->getConfig(Product::class, 'inventory_status');
                $class  = ExtendHelper::buildEnumValueClassName($config->get('enum_code'));

                $inventoryStatus = $this->databaseHelper->find($class, Product::INVENTORY_STATUS_IN_STOCK);
                $entity->setInventoryStatus($inventoryStatus);
            }
        }

        if (($entity = $this->setProductFamily($entity)) === null) {
            return null;
        }

        if (($entity = $this->setProductCategory($entity)) === null) {
            return null;
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param Product $product
     *
     * @return Product|null
     */
    protected function setProductFamily($product)
    {
        $oldFamily    = $product->getAttributeFamily();
        $newFamily = $this->context->getValue('itemData')['family'];
        if (!$oldFamily || $oldFamily->getCode() != $newFamily) {
            $attributeFamily = $this->doctrineHelper->getEntityRepositoryForClass(AttributeFamily::class)->findOneBy(['code' => $newFamily]);

            if (!$attributeFamily) {
                $this->context->incrementErrorEntriesCount();
                $this->strategyHelper->addValidationErrors(
                    [
                        $this->translator->trans('synolia.oroneo.import.product.error.family_not_found', [
                            '{{ family }}' => $newFamily,
                        ]),
                    ],
                    $this->context
                );

                return null;
            }
            $product->setAttributeFamily($attributeFamily);
        }

        return $product;
    }

    /**
     * @param Product $product
     *
     * @return Product|null
     */
    protected function setProductCategory($product)
    {
        $currentCategory = $this->getCategoryRepository()->findOneByProductSku($product->getSku());
        $categoryCodes   = explode(',', $this->context->getValue('itemData')['categories']);
        $categoryCode    = $categoryCodes[0];

        if (count($categoryCodes) > 1) {
            $this->logger->addWarning(
                $this->translator->trans('synolia.oroneo.import.product.error.multiple_category', [
                    '{{ productSku }}' => $product->getSku(),
                    '{{ category }}'   => $categoryCode,
                ])
            );
        }

        if (!$currentCategory || $currentCategory->getAkeneoCategoryCode() != $categoryCode) {
            $category = $this->getCategoryRepository()->findOneBy(['akeneoCategoryCode' => $categoryCode]);

            if (!$category) {
                $this->context->incrementErrorEntriesCount();
                $this->strategyHelper->addValidationErrors(
                    [
                        $this->translator->trans('synolia.oroneo.import.product.error.category_not_found', [
                            '{{ category }}' => $categoryCode,
                        ]),
                    ],
                    $this->context
                );

                return null;
            }

            if ($currentCategory) {
                $currentCategory->removeProduct($product);
                $this->doctrineHelper->getEntityManagerForClass(get_class($currentCategory))->persist($currentCategory);
            }

            $category->addProduct($product);
            $this->doctrineHelper->getEntityManagerForClass(get_class($category))->persist($category);
        }

        return $product;
    }

    /**
     * {@inheritdoc}
     */
    public function findExistingEntity($entity, array $searchContext = [])
    {
        $entityName = ClassUtils::getClass($entity);
        $identifier = $this->databaseHelper->getIdentifier($entity);
        $existingEntity = null;

        // find by identifier
        if ($identifier || (is_string($identifier) && strlen($identifier) != 0)) {
            $existingEntity = $this->databaseHelper->find($entityName, $identifier);
        }

        if ($existingEntity) {
            return $existingEntity;
        }

        return parent::findExistingEntity($entity, $searchContext);
    }

    /**
     * @return BusinessUnitRepository
     */
    protected function getBusinessUnitRepository()
    {
        if (!$this->businessUnitRepo) {
            $this->businessUnitRepo = $this->doctrineHelper->getEntityRepositoryForClass('OroOrganizationBundle:BusinessUnit');
        }

        return $this->businessUnitRepo;
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getProductUnitRepository()
    {
        if (!$this->productUnitRepo) {
            $this->productUnitRepo = $this->doctrineHelper->getEntityRepositoryForClass('OroProductBundle:ProductUnit');
        }

        return $this->productUnitRepo;
    }

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        if (!$this->categoryRepo) {
            $this->categoryRepo = $this->doctrineHelper->getEntityRepositoryForClass('OroCatalogBundle:Category');
        }

        return $this->categoryRepo;
    }
}
