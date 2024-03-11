<?php

namespace App\Repository;

use App\Controller\ImageUploadController;
use App\Entity\UploadedImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UploadedImage>
 *
 * @method UploadedImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method UploadedImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method UploadedImage[]    findAll()
 * @method UploadedImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UploadedImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UploadedImage::class);
    }

    /**
     * @param int $id
     * @return UploadedImage|null
     */
    public function getById(int $id): ?UploadedImage
    {
        return $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $currentPage
     * @return Paginator
     */
    public function getAll(int $currentPage = 1): Paginator
    {
        $query = $this->createQueryBuilder('a')
            ->orderBy('a.uploadedDate', 'DESC')
            ->getQuery();

        return $this->paginate($query, $currentPage, ImageUploadController::PAGINATE);
    }

    /**
     *
     * @param Query $dql
     * @param integer $page
     * @param integer $limit
     *
     * @return Paginator
     */
    public function paginate(Query $dql, int $page = 1, int $limit = 10): Paginator
    {
        $paginator = new Paginator($dql);

        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }

//    /**
//     * @return UploadedImage[] Returns an array of UploadedImage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UploadedImage
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
