<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Spyck\IngestionBundle\Entity\Job;

class EntityService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getEntityByJob(Job $job): ?object
    {
        $adapter = $job->getSource()->getModule()->getAdapter();

        /** RepositoryInterface $repository */
        $repository = $this->entityManager->getRepository($adapter);

        return $repository->createQueryBuilder('entity')
            ->where('entity.job = :job')
            ->setParameter('job', $job)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
