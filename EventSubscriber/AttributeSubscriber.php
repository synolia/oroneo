<?php

namespace Synolia\Bundle\OroneoBundle\EventSubscriber;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Synolia\Bundle\OroneoBundle\Command\ImportCommand;

/**
 * Class AttributeSubscriber
 * @package Synolia\Bundle\OroneoBundle\EventSubscriber
 */
class AttributeSubscriber implements EventSubscriberInterface
{
    /** @var  EntityProcessor */
    protected $entityProcessor;

    /** @var  ConfigManager */
    protected $entityFieldManager;

    /**
     * AttributeSubscriber constructor.
     *
     * @param EntityProcessor $entityProcessor
     * @param ConfigManager   $entityFieldManager
     */
    public function __construct(EntityProcessor $entityProcessor, ConfigManager $entityFieldManager)
    {
        $this->entityProcessor   = $entityProcessor;
        $this->entityFieldManager = $entityFieldManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::TERMINATE => 'onTerminate',
        ];
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onTerminate(ConsoleTerminateEvent $event)
    {
        if ($event->getCommand() instanceof ImportCommand) {
            $type = $event->getInput()->getArgument(ImportCommand::ARGUMENT_TYPE);
            if ($type == 'attribute') {
                $product = $this->entityFieldManager->getConfigEntityModel(Product::class);
                $config  = $product->toArray('extend');

                if ($config['state'] == ExtendScope::STATE_UPDATE) {
                    $event->getOutput()->writeln('<info>Updating schema</info>');
                    $this->entityProcessor->updateDatabase();
                    $event->getOutput()->writeln('<info>Schema updated</info>');
                }
            }
        }
    }
}
