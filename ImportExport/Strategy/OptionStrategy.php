<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Doctrine\Common\Util\ClassUtils;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\EnumValueTranslation;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Synolia\Bundle\OroneoBundle\Manager\MappingManager;

/**
 * Class OptionStrategy
 * @package Synolia\Bundle\OroneoBundle\ImportExport\Strategy
 */
class OptionStrategy extends ConfigurableAddOrReplaceStrategy
{
    /** @var MappingManager */
    protected $mappingManager;

    /** @var LocalizationRepository */
    protected $localizationRepository;

    /** @var array */
    protected $locales = null;

    /**
     * @param MappingManager $mappingManager
     */
    public function setMappingManager(MappingManager $mappingManager)
    {
        $this->mappingManager = $mappingManager;
    }

    /**
     * @param LocalizationRepository $localizationRepository
     */
    public function setLocalizationRepository(LocalizationRepository $localizationRepository)
    {
        $this->localizationRepository = $localizationRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function afterProcessEntity($entity)
    {
        $this->setEntityId($entity); // If the option is new, the entity ID is set to null in the processEntity

        /** @var TranslationRepository $translationRepository */
        $translationRepository = $this->doctrineHelper->getEntityRepositoryForClass(EnumValueTranslation::class);
        $locales               = $this->getLocales();

        $labelKey  = array_search('name', $this->mappingManager->getMappings());
        $itemData  = $this->context->getValue('itemData');

        foreach ($locales as $akeneoLocale => $oroCode) {
            $translationKey = $labelKey.'-'.$akeneoLocale;

            if (!isset($itemData[$translationKey])) {
                continue;
            }

            $translationRepository->translate($entity, 'name', $oroCode, $itemData[$translationKey]);
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param object $entity
     */
    protected function setEntityId($entity)
    {
        if ($entity->getId() == null) {
            $entityId = $this->context->getValue('itemData')['id'];

            if (!empty($entityId) || $entityId == '0') {
                $this->fieldHelper->setObjectValue($entity, 'id', $entityId);
            }
        }
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
     * Override to only search options based on their id and not their value
     *
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        $entityName = ClassUtils::getClass($entity);

        if ($entity instanceof AbstractEnumValue) {
            $existingEntity = $this->databaseHelper->find($entityName, $entity->getId());

            return $existingEntity;
        }

        return parent::findExistingEntity($entity, $searchContext);
    }

    /**
     * Override for better detection of status
     *
     * {@inheritdoc}
     */
    protected function updateContextCounters($entity)
    {
        if ($this->findExistingEntity($entity)) {
            $this->context->incrementReplaceCount();
        } else {
            $this->context->incrementAddCount();
        }
    }
}
