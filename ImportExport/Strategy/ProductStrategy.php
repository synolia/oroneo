<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use \Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy as Strategy;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class ProductStrategy
 */
class ProductStrategy extends Strategy
{
    /** @var ConfigManager */
    protected $configManager;

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

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;

        $this->businessUnitRepo = $doctrineHelper->getEntityRepositoryForClass('OroOrganizationBundle:BusinessUnit');
        $this->productUnitRepo  = $doctrineHelper->getEntityRepositoryForClass('OroProductBundle:ProductUnit');
        $this->categoryRepo     = $doctrineHelper->getEntityRepositoryForClass('OroCatalogBundle:Category');
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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
        $currentCategory = $this->categoryRepo->findOneByProductSku($product->getSku());
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
            $category = $this->categoryRepo->findOneBy(['akeneoCategoryCode' => $categoryCode]);

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
    protected function findExistingEntity($entity, array $searchContext = [])
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
}
