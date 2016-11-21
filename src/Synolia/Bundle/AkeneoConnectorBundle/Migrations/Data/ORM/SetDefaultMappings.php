<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Synolia\Bundle\AkeneoConnectorBundle\SystemConfig\MappingConfig;

/**
 * Class SetDefaultMappings
 * @package Synolia\Bundle\AkeneoConnectorBundle\Migrations\Data\ORM
 */
class SetDefaultMappings extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set(
            'synolia_akeneo_connector.category_mapping',
            [
                new MappingConfig('code', 'akeneoCategoryCode', '', true, false),
                new MappingConfig('label', 'titles', 'string', true, true),
                new MappingConfig('parent', 'parentCategory', 'akeneoCategoryCode', true, false),
            ]
        );
        $configManager->set(
            'synolia_akeneo_connector.option_mapping',
            [
                new MappingConfig('code', 'id', '', true, false),
                new MappingConfig('label-fr_FR', 'name', '', true, false),
                new MappingConfig('sort_order', 'priority', '', true, false),
                new MappingConfig('attribute', 'attribute', '', true, false),
            ]
        );
        $configManager->set(
            'synolia_akeneo_connector.attribute_mapping',
            [
                new MappingConfig('code', 'fieldName', '', true, false),
                new MappingConfig('type', 'type', '', true, false),
                new MappingConfig('label-fr_FR', 'entity.label', '', true, false),
                new MappingConfig('useable_as_grid_filter', 'datagrid.show_filter', '', true, false),
                new MappingConfig('sort_order', 'view.priority', '', false, false),
                new MappingConfig('max_characters', 'extend.length', '', false, false),
                new MappingConfig('max_file_size', 'attachment.maxsize', '', false, false),
            ]
        );
        $configManager->set('synolia_akeneo_connector.product_channel', 'ecommerce');
        $configManager->set(
            'synolia_akeneo_connector.product_mapping',
            [
                new MappingConfig('sku', 'sku', '', true, false),
                new MappingConfig('name', 'names', 'string', true, true),
                new MappingConfig('description', 'descriptions', 'text', false, true),
                new MappingConfig('shortDescription', 'shortDescriptions', 'string', false, true),
            ]
        );
        $configManager->flush();
    }
}
