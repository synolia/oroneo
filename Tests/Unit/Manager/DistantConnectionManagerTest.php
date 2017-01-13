<?php

namespace Synolia\Bundle\OroneoBundle\Tests\Unit\Manager;

use Synolia\Bundle\OroneoBundle\Helper\FtpHelper;
use Synolia\Bundle\OroneoBundle\Helper\SftpHelper;
use Synolia\Bundle\OroneoBundle\Manager\DistantConnectionManager;

/**
 * Class DistantConnectionManagerTest
 * @package Synolia\Bundle\OroneoBundle\Tests\Unit\Manager
 */
class DistantConnectionManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DistantConnectionManager $connectionManager
     */
    protected $connectionManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FtpHelper
     */
    protected $ftpHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SftpHelper
     */
    protected $sftpHelper;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->ftpHelper  = $this->getMockForClass('Synolia\Bundle\OroneoBundle\Helper\FtpHelper');
        $this->sftpHelper = $this->getMockForClass('Synolia\Bundle\OroneoBundle\Helper\SftpHelper');

        $this->connectionManager = new DistantConnectionManager($this->ftpHelper, $this->sftpHelper);
    }

    /**
     * @dataProvider getTypeMimeProvider
     *
     * @param string $filename
     * @param string $expectedResult
     */
    public function testGetMimeType($filename, $expectedResult)
    {
        $method = new \ReflectionMethod(
            'Synolia\Bundle\OroneoBundle\Manager\DistantConnectionManager',
            'getMimeType'
        );
        $method->setAccessible(true);
        $this->assertEquals($expectedResult, $method->invokeArgs($this->connectionManager, [$filename]));
    }

    /**
     * @return array
     */
    public function getTypeMimeProvider()
    {
        return [
            'with zip extension' => [
                'test.zip',
                'application/zip',
            ],
            'with csv extention' => [
                'test.csv',
                'text/csv',
            ],
            'with multiple extensions ended by zip' => [
                'test.test.test.csv.test.zip',
                'application/zip',
            ],
            'with multiple extensions ended by csv' => [
                'test.test.test.csv.test.csv',
                'text/csv',
            ],
            'without extension' => [
                'test',
                'text/csv',
            ],
            'blank filename' => [
                '',
                'text/csv',
            ],
        ];
    }

    /**
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockForClass($className)
    {
        return $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
