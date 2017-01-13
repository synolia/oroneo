<?php

namespace Synolia\Bundle\OroneoBundle\Tests\Unit\ImportExport\Step;

use Akeneo\Bundle\BatchBundle\Job\BatchStatus;
use Akeneo\Bundle\BatchBundle\Job\ExitStatus;
use Synolia\Bundle\OroneoBundle\ImportExport\Step\SchemaUpdateStep;

class SchemaUpdateStepTest extends \PHPUnit_Framework_TestCase
{
    const STEP_NAME = 'test_step_name';

    /** @var SchemaUpdateStep */
    protected $itemStep = null;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher = null;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $jobRepository = null;

    public function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');
        $this->jobRepository   = $this->getMock('Akeneo\\Bundle\\BatchBundle\\Job\\JobRepositoryInterface');

        $this->itemStep = new SchemaUpdateStep(self::STEP_NAME);

        $this->itemStep->setEventDispatcher($this->eventDispatcher);
        $this->itemStep->setJobRepository($this->jobRepository);
    }

    public function testExecute()
    {

        $stepExecution = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Entity\\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(new BatchStatus(BatchStatus::STARTING)));
        $stepExecution->expects($this->any())
            ->method('getExitStatus')
            ->will($this->returnValue(new ExitStatus()));

        $updater = $this->getMockBuilder('Synolia\Bundle\OroneoBundle\ImportExport\Item\SchemaUpdater')
            ->setMethods(['setStepExecution', 'update'])
            ->getMock();

        $updater
            ->expects($this->once())
            ->method('setStepExecution')
            ->with($stepExecution);

        $updater
            ->expects($this->once())
            ->method('update');

        $this->itemStep->setUpdater($updater);
        $this->itemStep->execute($stepExecution);
    }
}
