<?php

namespace Synolia\Bundle\OroneoBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Synolia\Bundle\OroneoBundle\SystemConfig\MappingLocalization;

/**
 * Class SetDefaultLocalizationMappings
 * @package Synolia\Bundle\OroneoBundle\Migrations\Data\ORM
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
            'synolia_oroneo.localization_mapping',
            [
                new MappingLocalization('en_US', 'default'),
            ]
        );
        $configManager->flush();
    }
}
