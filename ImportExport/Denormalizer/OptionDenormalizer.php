<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Denormalizer;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Class OptionDenormalizer
 * @package   Synolia\Bundle\OroneoBundle\ImportExport\Denormalizer
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class OptionDenormalizer implements DenormalizerInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigProvider  */
    protected $provider;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $reflection = $this->getReflectionClass($data['attribute']);
        $args       = [
            'id'       => $data['id'],
            'name'     => empty($data['name']) ? '' : $data['name'],
            'priority' => empty($data['priority']) ? 0 : $data['priority'],
        ];

        return $reflection->newInstanceArgs($args);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue', true) && isset($data['attribute']);
    }

    /**
     * @param string $attribute
     *
     * @return \ReflectionClass
     * @throws SerializerException
     */
    protected function getReflectionClass($attribute)
    {
        $provider = $this->getProvider();

        if (!$provider->hasConfig(Product::class, $attribute)) {
            throw new SerializerException('Attribute '.$attribute.' does not exist');
        }
        $config = $provider->getConfig(Product::class, $attribute);
        $class  = ExtendHelper::buildEnumValueClassName($config->get('enum_code'));

        return new \ReflectionClass($class);
    }

    /**
     * @return ConfigProvider
     */
    protected function getProvider()
    {
        if (!$this->provider) {
            $this->provider = $this->configManager->getProvider('enum');
        }

        return $this->provider;
    }
}
