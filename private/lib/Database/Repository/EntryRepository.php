<?php declare(strict_types=1);

namespace App\Database\Repository;

use App\Database\Model\Entry;
use App\Database\Model\User;
use Doctrine\ORM\QueryBuilder;

class EntryRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected const RESOURCE_NAME = Entry::class;

    /**
     * @return Entry[]
     */
    public function findByUser(User $user): array
    {
        return $this->db->getRepository(self::RESOURCE_NAME)
            ->findBy(['referencedUser' => $user]);
    }

    /**
     * @return Entry[]
     */
    public function findByUserIdAndCategoryId(int $userId, int $categoryId): array
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('e')
            ->from(self::RESOURCE_NAME, 'e')
            ->where('e.referencedCategory = :categoryId AND e.referencedUser = :userId')
            ->setParameter('categoryId', $categoryId)
            ->setParameter('userId', $userId);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Entry[]
     */
    public function getEntriesBySearchQueryLimitCategoryStartEndDateAndOffset(
        int $userId,
        ?string $search,
        ?int $categoryId,
        ?int $startCreatedDate,
        ?int $endCreatedDate,
        ?int $offset,
        ?int $limit
    ): array {
        $qb = $this->db->createQueryBuilder();

        $qb->select('e');

        $this->addParametersToQueryBuilderSearchCategoryStartEndDate(
            $qb,
            $userId,
            $search,
            $categoryId,
            $startCreatedDate,
            $endCreatedDate
        );

        if ($limit !== null) {
            $offset = $offset ?? 0;

            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function getTotalCountOfEntriesBySearchQueryLimitCategoryStartEndDateAndOffset(
        int $userId,
        ?string $search,
        ?int $categoryId,
        ?int $startCreatedDate,
        ?int $endCreatedDate
    ): int {
        $qb = $this->db->createQueryBuilder();

        $qb->select('count(e.id)');

        $this->addParametersToQueryBuilderSearchCategoryStartEndDate(
            $qb,
            $userId,
            $search,
            $categoryId,
            $startCreatedDate,
            $endCreatedDate
        );

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    private function addParametersToQueryBuilderSearchCategoryStartEndDate(
        QueryBuilder $qb,
        $userId,
        ?string $search,
        ?int $categoryId,
        ?int $startCreatedDate,
        ?int $endCreatedDate
    ): void
    {
        $qb->from(self::RESOURCE_NAME, 'e')
            ->where('e.referencedUser = :userId')
            ->orderBy('e.createdTimestamp', 'DESC')
            ->setParameter('userId', $userId);

        if ($categoryId !== null) {
            $qb->andWhere('e.referencedCategory = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($startCreatedDate !== null && $endCreatedDate !== null) {
            $qb->andWhere('e.createdTimestamp BETWEEN :startTime AND :endTime')
                ->setParameter('startTime', $startCreatedDate)
                ->setParameter('endTime', $endCreatedDate);
        }

        if ($search !== null) {
            $qb->andWhere($qb->expr()->like('e.title', ':search'))
                ->setParameter('search', "%{$search}%");
        }
    }
}
