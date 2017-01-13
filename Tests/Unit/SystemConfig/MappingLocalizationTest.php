<?php

namespace Synolia\Bundle\OroneoBundle\Tests\Unit\SystemConfig;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Synolia\Bundle\OroneoBundle\SystemConfig\MappingLocalization;

class MappingLocalizationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['akeneoLocalization', 'akeneo'],
            ['oroLocalization', 'oro'],
        ];

        $this->assertPropertyAccessors(new MappingLocalization(), $properties);
    }

    public function testConstruct()
    {
        $mapping = new MappingLocalization();
        $this->assertNull($mapping->getAkeneoLocalization());
        $this->assertNull($mapping->getOroLocalization());

        $akeneoLocalization = 'akeneo';
        $oroLocalization    = 'oro';

        $mapping = new MappingLocalization($akeneoLocalization, $oroLocalization);

        $this->assertEquals($akeneoLocalization, $mapping->getAkeneoLocalization());
        $this->assertEquals($oroLocalization, $mapping->getOroLocalization());
    }
}
