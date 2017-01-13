<?php

namespace Synolia\Bundle\OroneoBundle\Tests\Unit\SystemConfig;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Synolia\Bundle\OroneoBundle\SystemConfig\MappingConfig;

class MappingConfigTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['akeneoField', 'akeneo'],
            ['oroField', 'oro'],
            ['oroEntityField', 'oro_entity', null],
            ['required', true],
            ['translatable', false],
        ];

        $this->assertPropertyAccessors(new MappingConfig(), $properties);
    }

    public function testConstruct()
    {
        $mapping = new MappingConfig();
        $this->assertNull($mapping->getAkeneoField());
        $this->assertNull($mapping->getOroField());
        $this->assertNull($mapping->getOroEntityField());
        $this->assertNull($mapping->isRequired());
        $this->assertNull($mapping->isTranslatable());

        $akeneoField    = 'akeneo';
        $oroField       = 'oro';
        $oroEntityField = 'oro_entity';
        $required       = true;
        $translatable   = false;

        $mapping = new MappingConfig($akeneoField, $oroField, $oroEntityField, $required, $translatable);

        $this->assertEquals($akeneoField, $mapping->getAkeneoField());
        $this->assertEquals($oroField, $mapping->getOroField());
        $this->assertEquals($oroEntityField, $mapping->getOroEntityField());
        $this->assertEquals($required, $mapping->isRequired());
        $this->assertEquals($translatable, $mapping->isTranslatable());
    }
}
