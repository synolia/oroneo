<?php

namespace Synolia\Bundle\OroneoBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Synolia\Bundle\OroneoBundle\DependencyInjection\SynoliaOroneoExtension;

class SystemConfigListener
{
    const DEFAULT_OWNER        = 'default_owner';
    const DEFAULT_ORGANIZATION = 'default_organization';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $ownerClass;

    /**
     * @var string
     */
    protected $organizationClass;

    /**
     * @param ManagerRegistry $registry
     * @param string $userClass
     * @param string $organizationClass
     */
    public function __construct(ManagerRegistry $registry, $userClass, $organizationClass)
    {
        $this->registry = $registry;
        $this->ownerClass = $userClass;
        $this->organizationClass = $organizationClass;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onFormPreSetData(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();
        $ownerKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, [SynoliaOroneoExtension::ALIAS, self::DEFAULT_OWNER]);
        if (is_array($settings) && array_key_exists($ownerKey, $settings)) {
            $settings[$ownerKey]['value'] = $this->registry
                ->getManagerForClass($this->ownerClass)
                ->find($this->ownerClass, $settings[$ownerKey]['value']);
            $event->setSettings($settings);
        }

        $organizationKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, [SynoliaOroneoExtension::ALIAS, self::DEFAULT_ORGANIZATION]);
        if (is_array($settings) && array_key_exists($organizationKey, $settings)) {
            $settings[$organizationKey]['value'] = $this->registry
                ->getManagerForClass($this->organizationClass)
                ->find($this->organizationClass, $settings[$organizationKey]['value']);
            $event->setSettings($settings);
        }
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();
        $ownerKey = implode(ConfigManager::SECTION_MODEL_SEPARATOR, [SynoliaOroneoExtension::ALIAS, self::DEFAULT_OWNER]);
        if (is_array($settings)
            && array_key_exists($ownerKey, $settings)
            && is_a($settings[$ownerKey]['value'], $this->ownerClass)
        ) {
            /** @var object $owner */
            $owner = $settings[$ownerKey]['value'];
            $settings[$ownerKey]['value'] = $owner->getId();
            $event->setSettings($settings);
        }

        $organizationKey = implode(ConfigManager::SECTION_MODEL_SEPARATOR, [SynoliaOroneoExtension::ALIAS, self::DEFAULT_ORGANIZATION]);
        if (is_array($settings)
            && array_key_exists($organizationKey, $settings)
            && is_a($settings[$organizationKey]['value'], $this->organizationClass)
        ) {
            /** @var object $organization */
            $organization = $settings[$organizationKey]['value'];
            $settings[$organizationKey]['value'] = $organization->getId();
            $event->setSettings($settings);
        }
    }
}
