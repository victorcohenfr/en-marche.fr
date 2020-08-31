<?php

namespace App\Repository\TerritorialCouncil;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use App\Entity\TerritorialCouncil\Candidacy;
use App\Entity\TerritorialCouncil\TerritorialCouncilMembership;
use App\Entity\TerritorialCouncil\TerritorialCouncilQualityEnum;
use App\Repository\PaginatorTrait;
use App\Repository\UuidEntityRepositoryTrait;
use App\TerritorialCouncil\Candidacy\SearchAvailableMembershipFilter;
use App\TerritorialCouncil\Filter\MembersListFilter;
use App\ValueObject\Genders;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TerritorialCouncilMembershipRepository extends ServiceEntityRepository
{
    use UuidEntityRepositoryTrait;
    use PaginatorTrait;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TerritorialCouncilMembership::class);
    }

    /**
     * @return TerritorialCouncilMembership[]
     */
    public function findAvailableMemberships(Candidacy $candidacy, SearchAvailableMembershipFilter $filter): array
    {
        $membership = $candidacy->getMembership();

        $qb = $this
            ->createQueryBuilder('membership')
            ->addSelect('adherent', 'quality')
            ->innerJoin('membership.qualities', 'quality')
            ->innerJoin('membership.adherent', 'adherent')
            ->leftJoin('membership.candidacies', 'candidacy', Join::WITH, 'candidacy.membership = membership AND candidacy.election = :election')
            ->where('membership.territorialCouncil = :council')
            ->andWhere('candidacy IS NULL OR candidacy.status = :candidacy_draft_status')
            ->andWhere('quality.name = :quality')
            ->andWhere('membership.id != :membership_id')
            ->andWhere(sprintf('membership.id NOT IN (%s)',
                $this->createQueryBuilder('t1')
                    ->select('t1.id')
                    ->innerJoin('t1.qualities', 't2')
                    ->where('t1.territorialCouncil = :council')
                    ->andWhere('t2.name IN (:qualities)')
                    ->getDQL()
            ))
            ->andWhere('adherent.gender = :gender')
            ->setParameters([
                'candidacy_draft_status' => Candidacy::STATUS_DRAFT,
                'election' => $candidacy->getElection(),
                'council' => $membership->getTerritorialCouncil(),
                'quality' => $filter->getQuality(),
                'membership_id' => $membership->getId(),
                'gender' => $candidacy->isMale() ? Genders::FEMALE : Genders::MALE,
                'qualities' => TerritorialCouncilQualityEnum::FORBIDDEN_TO_CANDIDATE,
            ])
            ->orderBy('adherent.lastName')
            ->addOrderBy('adherent.firstName')
        ;

        if ($filter->getQuery()) {
            $qb
                ->andWhere('(adherent.firstName LIKE :query OR adherent.lastName LIKE :query)')
                ->setParameter('query', sprintf('%s%%', $filter->getQuery()))
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function searchByFilter(MembersListFilter $filter, int $page = 1, int $limit = 50): PaginatorInterface
    {
        return $this->configurePaginator($this->createFilterQueryBuilder($filter), $page, $limit);
    }

    public function countForReferentTags(array $referentTags): int
    {
        return (int) $this
            ->createQueryBuilderWithReferentTagsCondition($referentTags)
            ->select('COUNT(1)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function createQueryBuilderWithReferentTagsCondition(array $referentTags): QueryBuilder
    {
        return $this->createQueryBuilder('tcm')
            ->innerJoin('tcm.territorialCouncil', 'territorialCouncil')
            ->innerJoin('territorialCouncil.referentTags', 'referentTag')
            ->andWhere('referentTag IN (:tags)')
            ->setParameter('tags', $referentTags)
        ;
    }

    private function createFilterQueryBuilder(MembersListFilter $filter): QueryBuilder
    {
        $qb = $this
            ->createQueryBuilderWithReferentTagsCondition($filter->getReferentTags())
            ->addSelect('tcm', 'adherent', 'quality')
            ->leftJoin('tcm.adherent', 'adherent')
            ->leftJoin('tcm.qualities', 'quality')
        ;

        if (false !== \strpos($filter->getSort(), '.')) {
            $sort = $filter->getSort();
        } else {
            $sort = 'tcm.'.$filter->getSort();
        }

        $qb->orderBy($sort, 'd' === $filter->getOrder() ? 'DESC' : 'ASC');

        if ($lastName = $filter->getLastName()) {
            $qb
                ->andWhere('adherent.lastName LIKE :last_name')
                ->setParameter('last_name', '%'.$lastName.'%')
            ;
        }

        if ($firstName = $filter->getFirstName()) {
            $qb
                ->andWhere('adherent.firstName LIKE :first_name')
                ->setParameter('first_name', '%'.$firstName.'%')
            ;
        }

        if ($gender = $filter->getGender()) {
            switch ($gender) {
                case Genders::FEMALE:
                case Genders::MALE:
                    $qb
                        ->andWhere('adherent.gender = :gender')
                        ->setParameter('gender', $gender)
                    ;

                    break;
                case Genders::UNKNOWN:
                    $qb->andWhere('adherent.gender IS NULL');

                    break;
                default:
                    break;
            }
        }

        return $qb;
    }
}
