<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager as GlobalConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Strategy\EntityFieldImportStrategy;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Synolia\Bundle\OroneoBundle\Manager\MappingManager;

/**
 * Class AttributeStrategy
 */
class AttributeStrategy extends EntityFieldImportStrategy
{
    /** @var ValidatorInterface $validator */
    protected $validator;

    /** @var MappingManager */
    protected $mappingManager;

    /** @var MappingManager */
    protected $productMappingManager;

    /** @var LocalizationRepository */
    protected $localizationRepository;

    /** @var array */
    protected $productMappings = null;

    /** @var array */
    protected $locales = null;

    /**
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param MappingManager $mappingManager
     */
    public function setMappingManager($mappingManager)
    {
        $this->mappingManager = $mappingManager;
    }

    /**
     * @param MappingManager $productMappingManager
     */
    public function setProductMappingManager(MappingManager $productMappingManager)
    {
        $this->productMappingManager = $productMappingManager;
    }

    /**
     * @param LocalizationRepository $localizationRepository
     */
    public function setLocalizationRepository($localizationRepository)
    {
        $this->localizationRepository = $localizationRepository;
    }

     /**
     * {@inheritdoc}
     */
    public function beforeProcessEntity($entity)
    {
        if ($entity->getType() == 'image' || $entity->getType() == 'file') {
            return null; //Image and file attributes should not be created
        }

        $productMappings = $this->getProductMappings();

        if (isset($productMappings[$entity->getFieldName()])) {
            $entity->setFieldName($productMappings[$entity->getFieldName()]);

            return $entity;
        }

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
    public function afterProcessEntity($entity)
    {
        /** @var FieldConfigModel $entity */
        if ($entity != null) {
            if (!$this->isExistingEntity) {
                if (!in_array($entity->getType(), array_merge($this->fieldTypeProvider->getSupportedRelationTypes(), ['text', 'image', 'file']))) {
                    //Hiding the fields from the datagrid by default
                    $entity->fromArray('datagrid', ['is_visible' => 0, 'show_filter' => 0], []);
                }
                $extendOptions = $entity->toArray('extend');
                $extendOptions['owner'] = ExtendScope::OWNER_CUSTOM;

                $entity->fromArray('extend', $extendOptions);

                $entity->fromArray(
                    'attribute',
                    [
                        'is_attribute' => 1,
                    ]
                );
            } else {
                $existingEntity = $this->findExistingEntity($entity);
                $extendOptions  = $existingEntity->toArray('extend');

                if (isset($extendOptions['state']) && $extendOptions['state'] == ExtendScope::STATE_DELETE) {
                    $extendOptions['state'] = ExtendScope::STATE_RESTORE;
                    $entity->fromArray('extend', $extendOptions);
                }
            }
        }

        $entity = parent::afterProcessEntity($entity);

        if ($entity) {
            $this->prepareTranslations($entity);
        }

        return $entity;
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

        $this->assertEnvironment($entity);

        /** @var FieldConfigModel $entity */
        if (!$entity = $this->beforeProcessEntity($entity)) {
            return null;
        }

        if (!$entity = $this->processEntity($entity)) {
            return null;
        }

        if (!$entity = $this->afterProcessEntity($entity)) {
            return null;
        }

        if ($entity) {
            $entity = $this->validateAndUpdateContext($entity);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function processEntity(FieldConfigModel $entity)
    {
        $supportedTypes     = $this->fieldTypeProvider->getSupportedFieldTypes();
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
            if ($this->isSystemField($existingEntity)) {
                $entity->setType($existingEntity->getType());
            }

            if ($entity->getType() !== $existingEntity->getType()) {
                $this->addErrors($this->translator->trans('oro.entity_config.import.message.change_type_not_allowed'));

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
     * Get product mappings that correspond to system attributes
     *
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
     * Gets an arrays containing all locales used in mappings
     *
     * @return array
     */
    protected function getLocales()
    {
        if ($this->locales === null) {
            $localizationMappings = $this->mappingManager->getLocalizationMappings();

            $this->locales = [];

            foreach ($localizationMappings as $localizationMapping) {
                if ($localizationMapping->getOroLocalization() == 'default') {
                    continue;
                }

                $locale = $this->localizationRepository->findOneByName($localizationMapping->getOroLocalization());

                $this->locales[$localizationMapping->getAkeneoLocalization()] = $locale->getFormattingCode();
            }
        }

        return $this->locales;
    }

    /**
     * Prepares the label translations
     *
     * @param FieldConfigModel $entity
     */
    protected function prepareTranslations(FieldConfigModel $entity)
    {
        $locales  = $this->getLocales();
        $labelKey = array_search('entity.label', $this->mappingManager->getMappings());
        $itemData = $this->context->getValue('itemData');

        $attributeTranslations = [];
        foreach ($locales as $akeneoLocale => $oroCode) {
            $translationKey = $labelKey.'-'.$akeneoLocale;

            if (!isset($itemData[$translationKey])) {
                continue;
            }
            $attributeTranslations[$oroCode] = $itemData[$translationKey];
        }

        if (!empty($attributeTranslations)) {
            $labelTranslations = $this->context->getValue('labelTranslations');

            if ($labelTranslations === null) {
                $labelTranslations = [];
            }

            $labelTranslations[$entity->getFieldName()] = $attributeTranslations;

            $this->context->setValue('labelTranslations', $labelTranslations);
        }
    }
}
