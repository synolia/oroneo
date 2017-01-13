<?php

namespace Synolia\Bundle\OroneoBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Synolia\Bundle\OroneoBundle\SystemConfig\MappingConfig;

/**
 * Class SetDefaultMappings
 * @package Synolia\Bundle\OroneoBundle\Migrations\Data\ORM
 */
class SetDefaultMappings extends AbstractFixture implements ContainerAwareInterface
{
    const MASTER_CATEGORY_ID = 1;
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

        $configManager->set('synolia_oroneo.master_category', ['masterCategory' => self::MASTER_CATEGORY_ID]);
        $configManager->set(
            'synolia_oroneo.category_mapping',
            [
                new MappingConfig('code', 'akeneoCategoryCode', '', true, false),
                new MappingConfig('label', 'titles', 'string', true, true),
                new MappingConfig('parent', 'parentCategory', 'akeneoCategoryCode', true, false),
            ]
        );

        $configManager->set(
            'synolia_oroneo.attribute_mapping',
            [
                new MappingConfig('code', 'fieldName', '', true, false),
                new MappingConfig('type', 'type', '', true, false),
                new MappingConfig('label-en_US', '', 'entity.label', true, false),
                new MappingConfig('useable_as_grid_filter', '', 'datagrid.show_filter', true, false),
                new MappingConfig('sort_order', '', 'view.priority', false, false),
                new MappingConfig('max_characters', '', 'extend.length', false, false),
                new MappingConfig('max_file_size', '', 'attachment.maxsize', false, false),
            ]
        );

        $configManager->set(
            'synolia_oroneo.option_mapping',
            [
                new MappingConfig('code', 'id', '', true, false),
                new MappingConfig('label-en_US', 'name', '', true, false),
                new MappingConfig('sort_order', 'priority', '', true, false),
                new MappingConfig('attribute', 'oroneo', 'attribute', true, false),
            ]
        );

        $configManager->set('synolia_oroneo.product_channel', 'ecommerce');
        $configManager->set(
            'synolia_oroneo.product_mapping',
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
