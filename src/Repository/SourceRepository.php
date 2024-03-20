<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Spyck\IngestionBundle\Entity\Source;

class SourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Source::class);
    }

    public function getSourceById(int $id): ?Source
    {
        return $this->createQueryBuilder('source')
            ->innerJoin('source.module', 'module', Join::WITH, 'module.active = TRUE')
            ->where('source.id = :id')
            ->andWhere('source.active = TRUE')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, Source>
     */
    public function getSourceData(): array
    {
        return $this->createQueryBuilder('source')
            ->innerJoin('source.module', 'module', Join::WITH, 'module.active = TRUE')
            ->where('source.active = TRUE')
            ->getQuery()
            ->getResult();
    }
}
