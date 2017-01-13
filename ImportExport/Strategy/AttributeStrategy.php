<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager as GlobalConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Strategy\EntityFieldImportStrategy;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Synolia\Bundle\OroneoBundle\Manager\MappingManager;

/**
 * Class AttributeStrategy
 */
class AttributeStrategy extends EntityFieldImportStrategy
{
    /** @var EntityConfigManager $entityFieldManager */
    protected $entityFieldManager;

    /** @var GlobalConfigManager $globalConfigManager */
    protected $globalConfigManager;

    /** @var ValidatorInterface $validator */
    protected $validator;

    /** @var MappingManager */
    protected $productMappingManager;

    /** @var array */
    protected $productMappings = null;

    /**
     * @param EntityConfigManager $entityFieldManager
     */
    public function setEntityConfigManager(EntityConfigManager $entityFieldManager)
    {
        $this->entityFieldManager = $entityFieldManager;
    }

    /**
     * @param GlobalConfigManager $globalConfigManager
     */
    public function setGlobalConfigManager(GlobalConfigManager $globalConfigManager)
    {
        $this->globalConfigManager = $globalConfigManager;
    }

    /**
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param MappingManager $productMappingManager
     */
    public function setProductMappingManager(MappingManager $productMappingManager)
    {
        $this->productMappingManager = $productMappingManager;
    }

     /**
     * {@inheritdoc}
     */
    public function beforeProcessEntity($entity)
    {
        /** @var FieldConfigModel $entity */
        if ($this->context->getValue('itemData')['localizable']) {
            $target = $entity->getType() == 'string' ? 'string' : 'text';

            $entity->fromArray(
                'extend',
                [
                    'target_column'   => $target,
                    'target_entity'   => LocalizedFallbackValue::class,
                    'relation_key'    => RelationType::MANY_TO_MANY.'|'.Product::class.'|'.LocalizedFallbackValue::class.'|'.$entity->getFieldName(),
                    'target_grid'     => [
                        $target,
                    ],
                    'target_title'    => [
                        $target,
                    ],
                    'target_detailed' => [
                        $target,
                    ],
                    'cascade' => [
                        'persist',
                    ],
                    'without_default' => true,
                ],
                []
            );

            $entity->fromArray(
                'importexport',
                [
                    'full' => true,
                    'fallback_field' => $target,
                ],
                []
            );

            $entity->setType(RelationType::MANY_TO_MANY);
        }

        return parent::beforeProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function processEntity(FieldConfigModel $entity)
    {
        $supportedTypes     = $this->fieldTypeProvider->getSupportedFieldTypes();
        $supportedRelations = $this->fieldTypeProvider->getSupportedRelationTypes();
        $productMappings    = $this->getProductMappings();

        if ((string) $entity->getFieldName() === '') {
            $this->addErrors($this->translator->trans('oro.entity_config.import.message.invalid_field_name'));

            return null;
        } elseif (in_array($entity->getFieldName(), $productMappings)) {
            return null;
        }

        if (!in_array($entity->getType(), $supportedTypes, true) && !in_array($entity->getType(), $supportedRelations, true)) {
            $this->addErrors($this->translator->trans('oro.entity_config.import.message.invalid_field_type'));

            return null;
        }

        $existingEntity = $this->findExistingEntity($entity);
        $this->isExistingEntity = (bool) $existingEntity;
        if ($this->isExistingEntity) {
            if ($entity->getType() !== $existingEntity->getType()) {
                $this->addErrors($this->translator->trans('oro.entity_config.import.message.change_type_not_allowed'));

                return null;
            }
            if ($this->isSystemField($existingEntity)) {
                return null;
            }
        } else {
            $violations = $this->validator->validate($entity);

            if ($violations->count() != 0) {
                foreach ($violations as $violation) {
                    $this->addErrors($violation->getMessage());
                }

                return null;
            }
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        /** @var FieldConfigModel $entity */
        if ($entity != null) {
            if ($entity->getType() == 'image' || $entity->getType() == 'file') {
                $attachments = $entity->toArray('attachment');

                if (!isset($attachments['maxsize'])) {
                    $attachments['maxsize'] = $this->globalConfigManager->get('synolia_oroneo.attribute_file_max_size');
                }

                if ($entity->getType() == 'image') {
                    $attachments['width']  = $this->globalConfigManager->get('synolia_oroneo.attribute_image_width');
                    $attachments['height'] = $this->globalConfigManager->get('synolia_oroneo.attribute_image_height');
                }

                $entity->fromArray('attachment', $attachments, []);
            }

            if (!$entity->getId() && !in_array($entity->getType(), array_merge($this->fieldTypeProvider->getSupportedRelationTypes(), ['text', 'image', 'file']))) {
                //Hiding the fields from the datagrid by default
                $entity->fromArray('datagrid', ['is_visible' => 3, 'show_filter' => 0], []);
            }
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        /** @var FieldConfigModel $entity */
        $now = new \DateTime('now');
        $entity->setCreated($now);
        $entity->setUpdated($now);

        return parent::process($entity);
    }

    /**
     * @return array
     */
    protected function getProductMappings()
    {
        if ($this->productMappings === null) {
            $mappings = $this->productMappingManager->getFieldMappings();

            foreach ($mappings as $mapping) {
                $this->productMappings[] = $mapping->getAkeneoField();
            }
        }

        return $this->productMappings;
    }
}
