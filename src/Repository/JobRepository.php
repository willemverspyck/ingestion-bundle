<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Spyck\IngestionBundle\Entity\Job;
use Spyck\IngestionBundle\Entity\Source;
use Spyck\IngestionBundle\Utility\DataUtility;

class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Job::class);
    }

    public function getJobById(int $id): ?Job
    {
        return $this->createQueryBuilder('job')
            ->where('job.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getJobBySourceAndCode(Source $source, string $code): ?Job
    {
        return $this->createQueryBuilder('job')
            ->innerJoin('job.source', 'source', Join::WITH, 'source = :source')
            ->where('job.code = :code')
            ->setParameter('source', $source)
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getJobsBySourceAndActiveIsNull(Source $source): array
    {
        return $this->createQueryBuilder('job')
            ->innerJoin('job.source', 'source', Join::WITH, 'source = :source')
            ->where('job.active IS NULL')
            ->setParameter('source', $source)
            ->getQuery()
            ->getResult();
    }

    public function putJob(Source $source, string $code, ?array $data, ?array $messages = null): Job
    {
        $job = new Job();
        $job->setSource($source);
        $job->setCode($code);
        $job->setData($data);
        $job->setMessages($messages);
        $job->setProcessed(false);
        $job->setActive(true);

        $this->getEntityManager()->persist($job);
        $this->getEntityManager()->flush();

        return $job;
    }

    public function patchJob(Job $job, array $fields, ?array $data = null, ?array $messages = null, ?bool $processed = null, ?bool $active = null): void
    {
        if (in_array('data', $fields)) {
            $job->setData($data);
        }

        if (in_array('messages', $fields)) {
            $job->setMessages($messages);
        }

        if (in_array('processed', $fields)) {
            DataUtility::assert(null !== $processed);

            $job->setProcessed($processed);
        }

        if (in_array('active', $fields)) {
            $job->setActive($active);
        }

        $this->getEntityManager()->persist($job);
        $this->getEntityManager()->flush();
    }

    public function patchJobBySource(Source $source, array $fields, ?bool $active = null): void
    {
        $date = new DateTimeImmutable();

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->update(Job::class, 'job')
            ->set('job.timestampUpdated', ':date')
            ->where('job.source = :source')
            ->setParameter('date', $date)
            ->setParameter('source', $source);

        if (in_array('active', $fields, true)) {
            $queryBuilder
                ->set('active = :active')
                ->setParameter('active', $active);
        }

        $queryBuilder
            ->getQuery()
            ->execute();
    }
}
