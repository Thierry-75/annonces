<?php

namespace App\Repository;

use App\Entity\Annonce;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Annonce>
 */
class AnnonceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Annonce::class);
    }

    /**
     * @param $mots
     * @param $category
     * @return mixed
     */
    public function search($mots = null,$category = null)
    {
        $query = $this->createQueryBuilder('a')
            ->where('a.active = 1');
        if($mots !== null){
            $query->andWhere('MATCH_AGAINST(a.title, a.content) AGAINST (:mots boolean)>0')
                ->setParameter('mots',$mots);
        }
        if($category !==null){
            $query->leftJoin('a.categorie','c')
                ->andWhere('c.id= :id')
                ->setParameter('id',$category);

        }
        return $query->getQuery()->getResult();
    }

    /**
     * @param $page
     * @param $limit
     * @return void
     */
    public function findPaginateAnnounces($page,$limit){
        $query = $this->createQueryBuilder('a')
            ->where('a.active = 1')
            ->orderBy('a.createdAt')
            ->setFirstResult(($page * $limit) -$limit)
            ->setMaxResults($limit);
        return $query->getQuery()->getResult();
    }

    public function findTotalAnnounces(){
        $query = $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.active = 1');
        return $query->getQuery()->getSingleScalarResult();  // return un result under characters form
    }

    //    /**
    //     * @return Annonce[] Returns an array of Annonce objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Annonce
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
