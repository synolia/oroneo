<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Reader;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;

/**
 * Class AttributeGroupReader
 * @package   Synolia\Bundle\OroneoBundle\ImportExport\Reader
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class AttributeGroupReader extends CsvFileAndIteratorReader
{
    /** @var ArrayCollection|AttributeFamily[] */
    protected $families;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var string */
    protected $className;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function setManagerRegistry($managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    protected function getIterations()
    {
        if (null === $this->families) {
            $em             = $this->managerRegistry->getManagerForClass($this->className);
            $families       = $em->getRepository($this->className)->findAll();
            $this->families = new ArrayCollection($families);
        }

        return $this->families;
    }
}
