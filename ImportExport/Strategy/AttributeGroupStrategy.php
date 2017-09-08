<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Synolia\Bundle\OroneoBundle\ImportExport\Reader\CsvFileAndIteratorReader;

/**
 * Class AttributeGroupStrategy
 * @package   Synolia\Bundle\OroneoBundle\ImportExport\Strategy
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class AttributeGroupStrategy extends LocalizedFallbackValueAwareStrategy
{
    /** @var string */
    protected $className;

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        if (!$entity instanceof $this->className) {
            return parent::findExistingEntity($entity, $searchContext);
        }

        $family = $this->context->getValue(CsvFileAndIteratorReader::CURRENT_ITERATION);
        $repo   = $this->doctrineHelper->getEntityRepository($entity);

        return $repo->findOneBy([
            'code'            => $entity->getCode(),
            'attributeFamily' => $family,
        ]);
    }

    /**
     * @param AttributeGroup $entity
     *
     * @return object
     */
    protected function afterProcessEntity($entity)
    {
        if ($entity->getId()) {
            $entity->setAttributeFamily($this->context->getValue(CsvFileAndIteratorReader::CURRENT_ITERATION));

            // Do not allow empty attribute group label. Add code if nothing in label column.
            if (null === $entity->getDefaultLabel()) {
                $defaultLabel = new LocalizedFallbackValue();
                $defaultLabel->setString($entity->getCode());
                $entity->addLabel($defaultLabel);
            }

            return parent::afterProcessEntity($entity);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function processEntity(
        $entity,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = [],
        $entityIsRelation = false
    ) {
        if (ClassUtils::getClass($entity) === $this->localizedFallbackValueClass) {
            $isFullData = true;

            $locale = $entity->getLocalization();
            if ($locale != null && $locale->getId() == null) {
                $entity->setLocalization($this->findExistingEntity($locale));
            }
        }

        return parent::processEntity($entity, $isFullData, $isPersistNew, $itemData, $searchContext, $entityIsRelation);
    }

    /**
     * {@inheritdoc}
     */
    protected function importExistingEntity($entity, $existingEntity, $itemData = null, array $excludedFields = [])
    {
        if (ClassUtils::getClass($entity) == $this->className) {
            $excludedFields[] = 'attributeRelations';
        }

        parent::importExistingEntity($entity, $existingEntity, $itemData, $excludedFields);
    }
}
