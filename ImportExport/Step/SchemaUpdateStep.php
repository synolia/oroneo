<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Akeneo\Bundle\BatchBundle\Step\AbstractStep;
use Synolia\Bundle\OroneoBundle\ImportExport\Item\SchemaUpdater;

/**
 * Class UpdateSchemaStep
 * @package Synolia\Bundle\OroneoBundle\ImportExport\Step
 */
class SchemaUpdateStep extends AbstractStep
{
    /** @var  SchemaUpdater */
    protected $updater;

    /**
     * Provide the configuration of the step
     *
     * @return array
     */
    public function getConfiguration()
    {
        if ($this->updater instanceof AbstractConfigurableStepElement) {
            return $this->updater->getConfiguration();
        }

        return [];
    }

    /**
     * Set the configuration for the step
     *
     * @param array $config
     */
    public function setConfiguration(array $config)
    {
        if ($this->updater instanceof AbstractConfigurableStepElement) {
            $this->updater->setConfiguration($config);
        }
    }

    /**
     * @return SchemaUpdater
     */
    public function getUpdater()
    {
        return $this->updater;
    }

    /**
     * @param SchemaUpdater $updater
     */
    public function setUpdater($updater)
    {
        $this->updater = $updater;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(StepExecution $stepExecution)
    {
        $this->updater->setStepExecution($stepExecution);
        $this->updater->update();
    }
}
