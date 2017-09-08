<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Writer\EntityFieldWriter;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

/**
 * Class AttributeWriter
 * @package   Synolia\Bundle\OroneoBundle\ImportExport\Writer
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class AttributeWriter extends EntityFieldWriter implements StepExecutionAwareInterface
{
    /** @var StepExecution */
    protected $stepExecution;

    /** @var TranslationManager */
    protected $translationManager;

    protected $finalStatus;

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * @param TranslationManager $translationManager
     */
    public function setTranslationManager($translationManager)
    {
        $this->translationManager = $translationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $provider = $this->configManager->getProvider('extend');
        if (!$provider) {
            return;
        }

        $entityConfig      = $provider->getConfig(Product::class);
        $this->finalStatus = $entityConfig->get('state');
        $translations      = [];

        $preparedTranslations = $this->stepExecution->getExecutionContext()->get('labelTranslations');

        $updatedLocales = [];
        foreach ($items as $item) {
            $translations = array_merge($translations, $this->writeItem($item));

            if (isset($preparedTranslations[$item->getFieldName()])) {
                $config = $this->configManager->getProvider('entity')->getConfig($item->getEntity()->getClassName(), $item->getFieldName());
                $labelKey = $config->get('label');
                foreach ($preparedTranslations[$item->getFieldName()] as $locale => $value) {
                    $this->translationManager->saveTranslation(
                        $labelKey,
                        $value,
                        $locale,
                        TranslationManager::DEFAULT_DOMAIN,
                        Translation::SCOPE_UI
                    );
                    if (!isset($updatedLocales[$locale])) {
                        $updatedLocales[$locale] = $locale;
                        $this->translationManager->invalidateCache($locale);
                    }
                }
            }
        }

        $this->setEntityFinalState();

        $this->configManager->flush();

        $this->translationManager->flush();

        $this->translationHelper->saveTranslations($translations);

        $this->stepExecution->getExecutionContext()->remove('labelTranslations');
    }

    /**
     * {@inheritdoc}
     */
    protected function setExtendData(FieldConfigModel $configModel, $state)
    {
        $provider = $this->configManager->getProvider('extend');
        if (!$provider) {
            return;
        }

        $className = $configModel->getEntity()->getClassName();
        $fieldName = $configModel->getFieldName();

        $config = $provider->getConfig($className, $fieldName);

        // Retrieve field changeSet to see if we need a database update or not.
        $this->configManager->calculateConfigChangeSet($config);
        $changeSet = $this->configManager->getConfigChangeSet($config);

        if ($state == ExtendScope::STATE_UPDATE && empty($changeSet)) {
            // Check if the modification detection is enough or not.
            $state = $config->get('state');
        } else {
            $this->finalStatus = ExtendScope::STATE_UPDATE;
        }

        if ($config->get('owner') != ExtendScope::OWNER_SYSTEM) {
            parent::setExtendData($configModel, $state);
            $config->set('origin', 'Akeneo');
        }

        $this->configManager->persist($config);
    }

    protected function setEntityFinalState()
    {
        $provider     = $this->configManager->getProvider('extend');
        $entityConfig = $provider->getConfig(Product::class);

        $entityConfig->set('state', $this->finalStatus);
        $this->configManager->persist($entityConfig);
    }
}
