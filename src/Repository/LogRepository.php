<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Spyck\IngestionBundle\Entity\Log;
use Spyck\IngestionBundle\Entity\Source;
use Spyck\IngestionBundle\Utility\DataUtility;

class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Log::class);
    }

    public function getLogBySourceAndCode(Source $source, string $code): ?Log
    {
        return $this->createQueryBuilder('log')
            ->where('log.source = :source')
            ->andWhere('log.code = :code')
            ->setParameter('source', $source)
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function putLog(Source $source, string $code, ?array $data, ?array $messages = null): Log
    {
        $log = new Log();
        $log->setSource($source);
        $log->setCode($code);
        $log->setData($data);
        $log->setMessages($messages);
        $log->setProcessed(false);

        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();

        return $log;
    }

    public function patchLog(Log $log, array $fields, ?array $data = null, ?array $messages = null, ?bool $processed = null): void
    {
        if (in_array('data', $fields)) {
            $log->setData($data);
        }

        if (in_array('messages', $fields)) {
            $log->setMessages($messages);
        }

        if (in_array('processed', $fields)) {
            DataUtility::assert(null !== $processed);

            $log->setProcessed($processed);
        }

        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();
    }

    public function patchLogProcessedBySource(Source $source, bool $processed): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->update(Log::class, 'log')
            ->set('log.processed', ':processed')
            ->where('log.source = :source')
            ->setParameter('processed', $processed)
            ->setParameter('source', $source)
            ->getQuery()
            ->execute();
    }
}
