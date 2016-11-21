<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Synolia\Bundle\AkeneoConnectorBundle\SystemConfig\MappingLocalization;

/**
 * Class SetDefaultLocalizationMappings
 * @package Synolia\Bundle\AkeneoConnectorBundle\Migrations\Data\ORM
 */
class SetDefaultLocalizationMappings extends AbstractFixture implements ContainerAwareInterface
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
            'synolia_akeneo_connector.localization_mapping',
            [
                new MappingLocalization('en_US', 'default'),
            ]
        );
        $configManager->flush();
    }
}
