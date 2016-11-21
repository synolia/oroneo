<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\EventSubscriber;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataAuditBundle\EventListener\EntityListener;
use Oro\Bundle\EntityBundle\Event\OroEventManager;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Synolia\Bundle\AkeneoConnectorBundle\Command\ImportCommand;

/**
 * Class ProductSubscriber
 * @package Synolia\Bundle\AkeneoConnectorBundle\EventListener
 */
class ProductSubscriber implements EventSubscriberInterface
{
    /** @var OroEventManager $manager */
    protected $manager;

    /** @var  EntityListener $listener */
    protected $listener;

    /**
     * CategorySubscriber constructor.
     *
     * @param OroEntityManager $manager
     * @param EntityListener   $listener
     */
    public function __construct(OroEntityManager $manager, EntityListener $listener)
    {
        $this->manager  = $manager->getEventManager();
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'onCommand',
        ];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onCommand(ConsoleCommandEvent $event)
    {
        if ($event->getCommand() instanceof ImportCommand) {
            $type = $event->getInput()->getArgument(ImportCommand::ARGUMENT_TYPE);
            if ($type == 'product') {
                /**
                 * @TODO : fix this :
                 * In @see \Oro\Bundle\DataAuditBundle\Loggable\LoggableManager::getOldEntity
                 * the old values are badly fetched : only the first entry of arrays is recovered,
                 * making the import fail because it makes @see \OroB2B\Bundle\CatalogBundle\Entity\Category::$titles
                 * a LocalizedFallbackValue instead of a collection
                 */
                $this->manager->removeEventListener(
                    array('onFlush', 'loadClassMetadata', 'postPersist'),
                    $this->listener
                );
            }
        }
    }
}
