<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Item;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor;
use Oro\Bundle\PlatformBundle\Maintenance\Mode;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Class SchemaUpdater
 * @package   Synolia\Bundle\OroneoBundle\ImportExport\Item
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class SchemaUpdater extends AbstractConfigurableStepElement implements StepExecutionAwareInterface
{
    /** @var  EntityProcessor */
    protected $entityProcessor;

    /** @var  ConfigManager */
    protected $entityFieldManager;

    /** @var  StepExecution */
    protected $stepExecution;

    /** @var  Mode */
    protected $maintenanceMode;

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
     * @param Mode $maintenanceMode
     */
    public function setMaintenanceMode(Mode $maintenanceMode)
    {
        $this->maintenanceMode = $maintenanceMode;
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
            $this->entityProcessor->updateDatabase(true, false, true);
            $this->stepExecution->addSummaryInfo('synolia.oroneo.step.schema_updater.title', 'synolia.oroneo.step.schema_updater.done');
        } else {
            $this->stepExecution->addSummaryInfo('synolia.oroneo.step.schema_updater.title', 'synolia.oroneo.step.schema_updater.skipped');
        }

        if ($this->maintenanceMode->isOn()) {
            $this->maintenanceMode->off();
        }
    }
}
