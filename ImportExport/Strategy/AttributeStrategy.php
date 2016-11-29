<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Strategy\EntityFieldImportStrategy;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * Class AttributeStrategy
 */
class AttributeStrategy extends EntityFieldImportStrategy
{
    /** @var  ConfigManager $entityFieldManager */
    protected $entityFieldManager;

    /**
     * @param ConfigManager $entityFieldManager
     */
    public function setEntityConfigManager(ConfigManager $entityFieldManager)
    {
        $this->entityFieldManager = $entityFieldManager;
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
        $supportedTypes = $this->fieldTypeProvider->getSupportedFieldTypes();
        $supportedRelations = $this->fieldTypeProvider->getSupportedRelationTypes();

        if ((string) $entity->getFieldName() === '') {
            $this->addErrors($this->translator->trans('oro.entity_config.import.message.invalid_field_name'));

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
                //@TODO : add default values in parameters
                $attachments = $entity->toArray('attachment');

                if (!isset($attachments['maxsize'])) {
                    $attachments['maxsize'] = 1024;
                }

                if ($entity->getType() == 'image') {
                    $attachments['width']  = 50;
                    $attachments['height'] = 50;
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
}
