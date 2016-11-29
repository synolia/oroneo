<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

/**
 * Class OptionStrategy
 * @package Synolia\Bundle\OroneoBundle\ImportExport\Strategy
 */
class OptionStrategy extends ConfigurableAddOrReplaceStrategy
{
    /**
     * {@inheritdoc}
     */
    protected function afterProcessEntity($entity)
    {
        if ($entity->getId() === null) {
            $this->fieldHelper->setObjectValue($entity, 'id', $this->context->getValue('itemData')['id']);
        }

        return parent::afterProcessEntity($entity);
    }
}
