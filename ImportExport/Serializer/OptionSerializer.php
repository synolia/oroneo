<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Serializer;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Class OptionSerializer
 * @package   Synolia\Bundle\OroneoBundle\ImportExport\Serializer
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class OptionSerializer extends Serializer
{
    /** @var \Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider  */
    protected $provider;

    /** @var \ReflectionProperty */
    protected $reflectionProperty;

    /**
     * OptionSerializer constructor.
     *
     * @param array         $normalizers
     * @param array         $encoders
     * @param ConfigManager $configManager
     */
    public function __construct(array $normalizers, array $encoders, ConfigManager $configManager)
    {
        $this->provider = $configManager->getProvider('enum');

        $reflectionClass = new \ReflectionClass(AbstractEnumValue::class);
        $property        = $reflectionClass->getProperty('id');
        $property->setAccessible(true);

        $this->reflectionProperty = $property;

        parent::__construct($normalizers, $encoders);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if ($type == AbstractEnumValue::class) {
            if (!$this->provider->hasConfig(Product::class, $data['attribute'])) {
                throw new SerializerException('Attribute '.$data['attribute'].' does not exist');
            }

            $config = $this->provider->getConfig(Product::class, $data['attribute']);
            $type   = ExtendHelper::buildEnumValueClassName($config->get('enum_code'));
        }

        $entity = parent::denormalize($data, $type, $format, $context);

        if ($entity instanceof AbstractEnumValue && $entity->getId() === null && $data['id'] == '0') {
            $this->reflectionProperty->setValue($entity, $data['id']);
        }

        return $entity;
    }
}
