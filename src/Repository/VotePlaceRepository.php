<?php

namespace App\Repository;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use App\Assessor\Filter\AssociationVotePlaceFilter;
use App\Assessor\Filter\VotePlaceFilters;
use App\Entity\Adherent;
use App\Entity\AssessorOfficeEnum;
use App\Entity\AssessorRequest;
use App\Entity\VotePlace;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

class VotePlaceRepository extends AbstractAssessorRepository
{
    use GeoFilterTrait;
    use PaginatorTrait;

    public const ALIAS = 'vp';

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, VotePlace::class);
    }

    public function findMatchingProposals(Adherent $manager, VotePlaceFilters $filters): array
    {
        if (!$manager->isAssessorManager()) {
            return [];
        }

        $qb = $this->createQueryBuilder(self::ALIAS);

        $filters->apply($qb, self::ALIAS);

        self::addAndWhereManagedBy($qb, $manager);

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    public function countMatchingProposals(Adherent $manager, VotePlaceFilters $filters): int
    {
        if (!$manager->isAssessorManager()) {
            return 0;
        }

        $qb = $this->createQueryBuilder(self::ALIAS);

        $filters->apply($qb, self::ALIAS);

        self::addAndWhereManagedBy($qb, $manager);

        return (int) $qb
            ->select('COUNT(DISTINCT vp.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findMatchingVotePlaces(AssessorRequest $assessorRequest): array
    {
        $qb = $this->createQueryBuilder(self::ALIAS);

        self::addAndWhereAssessorRequestLocation($qb, $assessorRequest, self::ALIAS);
        self::addAndWhereOfficeAvailability($qb, $assessorRequest);

        $qb->addOrderBy('vp.name', 'ASC');

        if ($assessorRequest->getVotePlaceWishes()->count() > 0) {
            $votePlacesWishedIds = array_map(function ($votePlace) { return $votePlace->getId(); }, $assessorRequest->getVotePlaceWishes()->toArray());

            $votePlacesWished = clone $qb;
            $votePlacesWished->andWhere($votePlacesWished->expr()->in('vp.id', $votePlacesWishedIds));

            $qb->andWhere($votePlacesWished->expr()->notIn('vp.id', $votePlacesWishedIds));

            return array_merge(
                $votePlacesWished->getQuery()->getResult(),
                $qb->getQuery()->getResult()
            );
        }

        return $qb->getQuery()->getResult();
    }

    public static function addAndWhereOfficeAvailability(
        QueryBuilder $qb,
        AssessorRequest $assessorRequest,
        $alias = self::ALIAS
    ): QueryBuilder {
        if (AssessorOfficeEnum::HOLDER === $assessorRequest->getOffice()) {
            $qb->andWhere($alias.'.holderOfficeAvailable = true');
        } else {
            $qb->andWhere($alias.'.substituteOfficeAvailable = true');
        }

        return $qb;
    }

    private static function addAndWhereManagedBy(QueryBuilder $qb, Adherent $assessorManager): QueryBuilder
    {
        $codesFilter = $qb->expr()->orX();

        foreach ($assessorManager->getAssessorManagedArea()->getCodes() as $key => $code) {
            if (is_numeric($code)) {
                // Postal code prefix
                $codesFilter->add(
                    $qb->expr()->like(self::ALIAS.'.postalCode', ':code'.$key)
                );
                $qb->setParameter('code'.$key, $code.'%');
            } else {
                // Country
                $codesFilter->add($qb->expr()->eq(self::ALIAS.'.country', ':code'.$key));
                $qb->setParameter('code'.$key, $code);
            }
        }

        return $qb->andWhere($codesFilter);
    }

    public function findByCountry(string $country): array
    {
        return  $this
            ->createQueryBuilder('votePlace')
            ->andWhere('votePlace.country = :country')
            ->andWhere('votePlace.enabled = :true')
            ->setParameters([
                'country' => $country,
                'true' => true,
            ])
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByPostalCode(string $postalCode): array
    {
        return  $this
            ->createQueryBuilder('votePlace')
            ->andWhere('FIND_IN_SET(:postalCode, votePlace.postalCode) > 0')
            ->andWhere('votePlace.enabled = :true')
            ->setParameters([
                'postalCode' => $postalCode,
                'true' => true,
            ])
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllByIds(array $ids): array
    {
        return $this
            ->createQueryBuilder('votePlace')
            ->where('votePlace.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
         ;
    }

    /**
     * @return VotePlace[]|PaginatorInterface
     */
    public function findAllForFilter(AssociationVotePlaceFilter $filter, int $page, int $limit): PaginatorInterface
    {
        $qb = $this->createQueryBuilder(self::ALIAS);

        if ($tags = $filter->getTags()) {
            $this->applyGeoFilter($qb, $tags, self::ALIAS, self::ALIAS.'.country', self::ALIAS.'.postalCode');
        }

        if ($inseeCodes = $filter->getInseeCodes()) {
            $qb
                ->andWhere('SUBSTRING_INDEX('.self::ALIAS.'.code, \'_\', 1) IN (:insee_codes)')
                ->setParameter('insee_codes', $inseeCodes)
            ;
        }

        if ($postalCodes = $filter->getPostalCodes()) {
            $orx = new Orx();

            foreach ($postalCodes as $index => $postalCode) {
                $orx->add(sprintf('FIND_IN_SET(:postal_code_%s, %s.postalCode) > 0', $index, self::ALIAS));
                $qb->setParameter('postal_code_'.$index, $postalCode);
            }

            $qb->andWhere($orx);
        }

        if ($city = $filter->getCity()) {
            $qb
                ->andWhere(self::ALIAS.'.city LIKE :city')
                ->setParameter('city', sprintf('%s%%', $city))
            ;
        }

        if ($country = $filter->getCountry()) {
            $qb
                ->andWhere(self::ALIAS.'.country = :country')
                ->setParameter('country', $country)
            ;
        }

        if ($name = $filter->getName()) {
            $qb
                ->andWhere(sprintf('%s.name LIKE :name OR %s.alias LIKE :name', self::ALIAS, self::ALIAS))
                ->setParameter('name', sprintf('%%%s%%', $name))
            ;
        }

        $qb
            ->orderBy(self::ALIAS.'.city', 'ASC')
            ->addOrderBy(self::ALIAS.'.name', 'ASC')
        ;

        return $this->configurePaginator($qb, $page, $limit);
    }

    public function findLastByCodePrefix(string $codePrefix): ?VotePlace
    {
        return $this->createQueryBuilder('vp')
            ->where('vp.code LIKE :code')
            ->setParameter('code', $codePrefix.'_%')
            ->setMaxResults(1)
            ->orderBy('vp.code', 'DESC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
