<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\Repository;

use Doctrine\ORM\EntityManager;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * Class CategoryRepository
 */
class CategoryRepository
{
    /** @var EntityManager */
    protected $entityManager;

    /**
     * ContactService constructor.
     *
     * @param EntityManager $manager
     */
    public function __construct(EntityManager $manager)
    {
        $this->entityManager = $manager;
    }

    /**
     * @param string $akeneoCategoryCode
     * @return null|Category
     */
    public function getParentCategoryByAkeneoCategoryCode($akeneoCategoryCode)
    {
        $query = $this->entityManager->createQueryBuilder();
        $query->select('c')
            ->from('OroB2BCatalogBundle:Category', 'c')
            ->where('c.akeneoCategoryCode = :akeneoCategoryCode')
            ->setParameter('akeneoCategoryCode', $akeneoCategoryCode)
        ;

        return $query->getQuery()->getOneOrNullResult();
    }
}
