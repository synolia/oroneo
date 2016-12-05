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
 * @package Synolia\Bundle\OroneoBundle\ImportExport\Serializer
 */
class OptionSerializer extends Serializer
{
    /** @var \Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider  */
    protected $provider;

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

        return parent::denormalize($data, $type, $format, $context);
    }
}
