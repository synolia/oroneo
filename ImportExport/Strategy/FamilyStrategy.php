<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Synolia\Bundle\OroneoBundle\Manager\MappingManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Class FamilyStrategy
 */
class FamilyStrategy extends LocalizedFallbackValueAwareStrategy
{
    /**
     * Default group created for system attributes
     */
    const DEFAULT_GROUP = '_default';

    /** @var MappingManager */
    protected $productMappingManager;

    /** @var string */
    protected $entityClass;

    /** @var array */
    protected $productMappings;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var UserRepository */
    protected $userRepository;

    /** @var string */
    protected $imageGroup;

    /**
     * @param MappingManager $productMappingManager
     */
    public function setProductMappingManager($productMappingManager)
    {
        $this->productMappingManager = $productMappingManager;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->userRepository = $doctrineHelper->getEntityRepositoryForClass('OroUserBundle:User');
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param string $group
     */
    public function setImageGroup($group)
    {
        $this->imageGroup = $group;
    }

    /**
     * {@inheritdoc}
     */
    protected function afterProcessEntity($entity)
    {
        $entity = parent::afterProcessEntity($entity);

        /** @var AttributeFamily $entity */

        if (!$entity) {
            return $entity;
        }

        // Set owner if there is not.
        if (!$entity->getOwner()) {
            $owner = $this->userRepository->findOneById($this->configManager->get('synolia_oroneo.default_owner'));
            $entity->setOwner($owner);
        }

        // Do not allow empty family titles. Add code if nothing in label column.
        if (null === $entity->getDefaultLabel()) {
            $defaultLabel = new LocalizedFallbackValue();
            $defaultLabel->setString($entity->getCode());
            $entity->addLabel($defaultLabel);
        }

        $attributeList         = $this->getAttributesNames();
        $attributesByGroupCode = $this->getAttributesByGroupCode($attributeList);

        $this->removeUnusedGroups($entity, array_keys($attributesByGroupCode));

        foreach ($attributesByGroupCode as $groupCode => $attributes) {
            /** @var AttributeGroup $attributeGroup */
            $attributeGroup = $entity->getAttributeGroups()->filter(function ($attributeGroup) use ($groupCode) {
                return $attributeGroup->getCode() == $groupCode;
            })->first();

            if (!$attributeGroup) {
                $attributeGroup = $this->createAttributeGroup($entity, $groupCode);
            }

            $this->associateAttributes($attributeGroup, $attributes);
        }

        $entity->setEntityClass($this->entityClass);

        return $entity;
    }

    /**
     * Creates an attribute group and associates it to a family
     *
     * @param AttributeFamily $family
     * @param string          $groupCode
     *
     * @return AttributeGroup
     */
    protected function createAttributeGroup(AttributeFamily $family, $groupCode)
    {
        $attributeGroup = new AttributeGroup();
        $family->addAttributeGroup($attributeGroup);

        $label = new LocalizedFallbackValue();
        $label->setString($groupCode);

        $attributeGroup
            ->setCode($groupCode)
            ->setIsVisible($groupCode != self::DEFAULT_GROUP)
            ->addLabel($label);

        return $attributeGroup;
    }

    /**
     * Gets a list of attributes from their fieldName grouped by the attribute groups
     *
     * @param array $attributeNames
     *
     * @return FieldConfigModel[][]
     */
    protected function getAttributesByGroupCode($attributeNames)
    {
        /** @var FieldConfigModelRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass('OroEntityConfigBundle:FieldConfigModel');

        $query = $repository->createQueryBuilder('f');

        $query
            ->innerJoin('f.entity', 'e')
            ->innerJoin('f.indexedValues', 'a', 'WITH', 'a.scope = :attributeScope AND a.code = :attributeCode AND a.value = :attributeValue')
            ->innerJoin('f.indexedValues', 'v', 'WITH', 'v.scope = :scope AND v.code = :code')
            ->where($query->expr()->andX(
                $query->expr()->eq('e.className', ':className'),
                $query->expr()->orX(
                    $query->expr()->in('f.fieldName', ':fieldNames'),
                    $query->expr()->eq('v.value', ':value')
                )
            ))
            ->setParameters([
                'attributeScope' => 'attribute',
                'attributeCode'  => 'is_attribute',
                'attributeValue' => '1',
                'scope'          => 'extend',
                'code'           => 'owner',
                'className'      => $this->entityClass,
                'fieldNames'     => $attributeNames,
                'value'          => ExtendScope::OWNER_SYSTEM,
            ]);

        /** @var ConfigModelIndexValue[] $attributes */
        $attributes = $query->getQuery()->getResult();

        $groups = [];

        foreach ($attributes as $attribute) {
            $akeneoOptions = $attribute->toArray('akeneo');

            if (!isset($akeneoOptions['attribute_group'])) {
                if ($attribute->getFieldName() == 'images') {
                    $akeneoOptions['attribute_group'] = $this->imageGroup;
                } else {
                    $akeneoOptions['attribute_group'] = self::DEFAULT_GROUP;
                }
            }

            if (!isset($groups[$akeneoOptions['attribute_group']])) {
                $groups[$akeneoOptions['attribute_group']] = [];
            }

            $groups[$akeneoOptions['attribute_group']][] = $attribute;
        }

        return $groups;
    }

    /**
     * Associates attributes with a given group
     *
     * @param AttributeGroup     $attributeGroup
     * @param FieldConfigModel[] $attributes
     */
    protected function associateAttributes(AttributeGroup $attributeGroup, $attributes)
    {
        $newRelations = [];
        foreach ($attributes as $attribute) {
            $attributeRelation = new AttributeGroupRelation();
            $attributeRelation->setEntityConfigFieldId($attribute->getId());

            $newRelations[$attribute->getId()] = $attributeRelation;
        }

        /** @var AttributeGroupRelation[] $oldRelations */
        $oldRelations = $attributeGroup->getAttributeRelations();
        foreach ($oldRelations as $oldRelation) {
            if (!isset($newRelations[$oldRelation->getEntityConfigFieldId()])) {
                $attributeGroup->removeAttributeRelation($oldRelation);
            } else {
                unset($newRelations[$oldRelation->getEntityConfigFieldId()]);
            }
        }

        foreach ($newRelations as $newRelation) {
            $attributeGroup->addAttributeRelation($newRelation);
        }
    }

    /**
     * Removes unused attribute groups from a family
     *
     * @param AttributeFamily $entity
     * @param array           $groupCodes list of group codes to keep
     */
    protected function removeUnusedGroups(AttributeFamily $entity, $groupCodes)
    {
        $groupsToRemove  = $entity->getAttributeGroups()->filter(function ($attributeGroup) use ($groupCodes) {
            return !in_array($attributeGroup->getCode(), $groupCodes);
        });

        foreach ($groupsToRemove as $group) {
            $entity->removeAttributeGroup($group);
        }
    }

    /**
     * Get the list of attributes for the family from the data passed to the import
     *
     * @return array
     */
    protected function getAttributesNames()
    {
        $itemData        = $this->context->getValue('itemData');
        $attributeList   = explode(',', $itemData['attributes']);

        $productMappings = $this->getProductMappings();

        foreach ($attributeList as &$attribute) {
            if (isset($productMappings[$attribute])) {
                $attribute = $productMappings[$attribute];
            }
        }

        return $attributeList;
    }

    /**
     * @return array
     */
    protected function getProductMappings()
    {
        if ($this->productMappings === null) {
            $mappings = $this->productMappingManager->getFieldMappings();

            foreach ($mappings as $mapping) {
                if (!in_array($mapping->getOroField(), ['', 'oroneo'])) {
                    $this->productMappings[$mapping->getAkeneoField()] = $mapping->getOroField();
                }
            }
        }

        return $this->productMappings;
    }

    /**
     * @param object $entity
     *
     * @return null|object
     */
    protected function validateAndUpdateContext($entity)
    {
        if (!$entity->getOwner()) {
            $this->context->incrementErrorEntriesCount();
            $this->context->addError('No owner defined for this family : '. $entity->getCode());
        }

        return parent::validateAndUpdateContext($entity);
    }
}
