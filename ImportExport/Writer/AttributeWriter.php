<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Writer;

use Oro\Bundle\EntityConfigBundle\ImportExport\Writer\EntityFieldWriter;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Class AttributeWriter
 * @package Synolia\Bundle\OroneoBundle\ImportExport\Writer
 */
class AttributeWriter extends EntityFieldWriter
{
    protected $finalStatus;

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

        foreach ($items as $item) {
            $translations = array_merge($translations, $this->writeItem($item));
        }

        $this->setEntityFinalState();

        $this->configManager->flush();

        $this->translationHelper->saveTranslations($translations);
    }

    /**
     * {@inheritdoc}
     */
    protected function setExtendData($className, $fieldName, $state)
    {
        $provider = $this->configManager->getProvider('extend');
        if (!$provider) {
            return;
        }

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

        parent::setExtendData($className, $fieldName, $state);

        $config->set('origin', 'Akeneo');

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
