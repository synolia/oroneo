<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Item;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Class SchemaUpdater
 * @package Synolia\Bundle\OroneoBundle\ImportExport\Item
 */
class SchemaUpdater extends AbstractConfigurableStepElement implements StepExecutionAwareInterface
{
    /** @var  EntityProcessor */
    protected $entityProcessor;

    /** @var  ConfigManager */
    protected $entityFieldManager;

    /** @var  StepExecution */
    protected $stepExecution;

    /**
     * @param EntityProcessor $entityProcessor
     */
    public function setEntityProcessor(EntityProcessor $entityProcessor)
    {
        $this->entityProcessor = $entityProcessor;
    }

    /**
     * @param ConfigManager $entityFieldManager
     */
    public function setEntityFieldManager(ConfigManager $entityFieldManager)
    {
        $this->entityFieldManager = $entityFieldManager;
    }


    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * Return an array of fields for the configuration form
     *
     * @return array:array
     *
     */
    public function getConfigurationFields()
    {
        return [];
    }

    /**
     *
     */
    public function update()
    {
        $product = $this->entityFieldManager->getConfigEntityModel(Product::class);
        $config  = $product->toArray('extend');

        if ($config['state'] == ExtendScope::STATE_UPDATE) {
            $this->entityProcessor->updateDatabase();
            $this->stepExecution->addSummaryInfo('synolia.oroneo.step.schema_updater.title', 'synolia.oroneo.step.schema_updater.done');
        } else {
            $this->stepExecution->addSummaryInfo('synolia.oroneo.step.schema_updater.title', 'synolia.oroneo.step.schema_updater.skipped');
        }
    }
}
