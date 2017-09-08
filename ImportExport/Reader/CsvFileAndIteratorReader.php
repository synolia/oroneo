<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Reader;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\CsvFileReader;

/**
 * Class CsvFileAndIteratorReader
 * @package   Synolia\Bundle\OroneoBundle\ImportExport\Reader
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class CsvFileAndIteratorReader extends CsvFileReader
{
    const CURRENT_ITERATION = 'current_iteration';

    /** @var array */
    protected $currentLine = null;

    /**
     * {@inheritdoc}
     */
    public function read($context = null)
    {
        if (!$context instanceof ContextInterface) {
            $context = $this->getContext();
        }

        $iterations = $this->getIterations();

        if (null === $this->currentLine || !$iteration = $iterations->next()) {
            $this->currentLine = parent::read($context);
            $iteration         = $iterations->first();
        }

        $context->setValue(self::CURRENT_ITERATION, $iteration);

        return $this->currentLine;
    }

    /**
     * Gets the iterator for the secondary entry
     *
     * @return Collection
     */
    protected function getIterations()
    {
        throw new \LogicException('You must override the getIterations() method in the concrete class.');
    }
}
