<?php

namespace Synolia\Bundle\OroneoBundle\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Class CategoryRepository
 */
class CategoryRepository
{
    /** @var DoctrineHelper $doctrineHelper */
    protected $doctrineHelper;

    /**
     * ContactService constructor.
     *
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $akeneoCategoryCode
     * @return null|Category
     */
    public function getParentCategoryByAkeneoCategoryCode($akeneoCategoryCode)
    {
        $categoryRepository = $this->doctrineHelper->getEntityRepository('OroCatalogBundle:Category');
        $query = $categoryRepository->createQueryBuilder('c');
        $query->select('c')
            ->where('c.akeneoCategoryCode = :akeneoCategoryCode')
            ->setParameter('akeneoCategoryCode', $akeneoCategoryCode)
        ;

        return $query->getQuery()->getOneOrNullResult();
    }
}
