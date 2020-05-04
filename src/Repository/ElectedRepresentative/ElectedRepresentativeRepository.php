<?php

namespace App\Repository\ElectedRepresentative;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use App\ElectedRepresentative\Filter\ListFilter;
use App\Entity\ElectedRepresentative\ElectedRepresentative;
use App\Repository\PaginatorTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ElectedRepresentativeRepository extends ServiceEntityRepository
{
    use PaginatorTrait;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ElectedRepresentative::class);
    }

    /**
     * @return ElectedRepresentative[]|PaginatorInterface
     */
    public function searchByFilter(ListFilter $filter, int $page = 1, int $limit = 100): PaginatorInterface
    {
        return $this->configurePaginator(
            $this->createFilterQueryBuilder($filter),
            $page,
            $limit,
            static function (Query $query) {
                $query
                    ->useResultCache(true)
                    ->setResultCacheLifetime(1800)
                ;
            }
        );
    }

    public function countForReferentTags(array $referentTags): int
    {
        $qb = $this
            ->createQueryBuilder('er')
            ->select('COUNT(DISTINCT er.id)')
        ;
        $this->withActiveMandatesCondition($qb);

        return (int) $this
            ->withZoneCondition($qb, $referentTags)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function createFilterQueryBuilder(ListFilter $filter): QueryBuilder
    {
        $qb = $this
            ->createQueryBuilder('er')
            ->orderBy('er.'.$filter->getSort(), 'd' === $filter->getOrder() ? 'DESC' : 'ASC')
        ;

        $this->withActiveMandatesCondition($qb);
        $this->withZoneCondition($qb, $filter->getReferentTags());

        return $qb;
    }

    private function withActiveMandatesCondition(QueryBuilder $qb, string $alias = 'er'): QueryBuilder
    {
        return $qb
            ->leftJoin($alias.'.mandates', 'mandate')
            ->andWhere('mandate.finishAt IS NULL')
            ->andWhere('mandate.onGoing = 1')
            ->andWhere('mandate.isElected = 1')
        ;
    }

    private function withZoneCondition(QueryBuilder $qb, array $referentTags, string $alias = 'er'): QueryBuilder
    {
        if (!\in_array('mandate', $qb->getAllAliases(), true)) {
            $qb->leftJoin($alias.'.mandates', 'mandate');
        }

        return $qb
            ->leftJoin('mandate.zone', 'zone')
            ->leftJoin('zone.referentTags', 'tag')
            ->andWhere('tag IN (:tags)')
            ->setParameter('tags', $referentTags)
        ;
    }
}
