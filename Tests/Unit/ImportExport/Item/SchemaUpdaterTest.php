<?php

namespace Synolia\Bundle\OroneoBundle\Tests\Unit\ImportExport\Item;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Synolia\Bundle\OroneoBundle\ImportExport\Item\SchemaUpdater;

class SchemaUpdaterTest extends \PHPUnit_Framework_TestCase
{
    public function testUpdate()
    {
        $needingUpdateConfig = new EntityConfigModel();
        $needingUpdateConfig->fromArray(
            'extend',
            [
                'state' => ExtendScope::STATE_UPDATE
            ],
            []
        );

        $upToDateConfig = new EntityConfigModel();
        $upToDateConfig->fromArray(
            'extend',
            [
                'state' => ExtendScope::STATE_ACTIVE
            ],
            []
        );

        $entityFieldManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->setMethods(['getConfigEntityModel'])
            ->disableOriginalConstructor()
            ->getMock();

        $entityFieldManager
            ->expects($this->exactly(2))
            ->method('getConfigEntityModel')
            ->will($this->onConsecutiveCalls($needingUpdateConfig, $upToDateConfig));

        $entityProcessor = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor')
            ->setMethods(['updateDatabase'])
            ->disableOriginalConstructor()
            ->getMock();

        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->setMethods(['addSummaryInfo'])
            ->disableOriginalConstructor()
            ->getMock();

        $stepExecution
            ->expects($this->exactly(2))
            ->method('addSummaryInfo')
            ->withConsecutive(
                ['synolia.oroneo.step.schema_updater.title', 'synolia.oroneo.step.schema_updater.done'],
                ['synolia.oroneo.step.schema_updater.title', 'synolia.oroneo.step.schema_updater.skipped']
            );


        $schemaUpdater = new SchemaUpdater();

        $schemaUpdater->setEntityProcessor($entityProcessor);
        $schemaUpdater->setEntityFieldManager($entityFieldManager);
        $schemaUpdater->setStepExecution($stepExecution);

        $schemaUpdater->update(); //First one is done
        $schemaUpdater->update(); //Second one is skipped
    }
}
