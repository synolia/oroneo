<?php

namespace Synolia\Bundle\OroneoBundle\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Class CategoryRepository
 * Repository with useful function to basically retrieve parentCategories and such.
 * @package   Synolia\Bundle\OroneoBundle\Repository
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
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
     * Retrieve Category by custom AkeneoCategoryCode.
     *
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

    /**
     * Retrieve Category by its ID.
     *
     * @param integer $categoryId
     *
     * @return Category
     */
    public function getCategoryById($categoryId)
    {
        return $this->doctrineHelper->getEntityRepositoryForClass('OroCatalogBundle:Category')->findOneById($categoryId);
    }
}
