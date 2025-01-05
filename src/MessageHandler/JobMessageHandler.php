<?php

namespace Spyck\IngestionBundle\MessageHandler;

use Spyck\IngestionBundle\Message\JobMessageInterface;
use Spyck\IngestionBundle\Repository\JobRepository;
use Spyck\IngestionBundle\Service\JobService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
final class JobMessageHandler
{
    public function __construct(private readonly JobRepository $jobRepository, private readonly JobService $jobService)
    {
    }

    public function __invoke(JobMessageInterface $jobMessage): void
    {
        $id = $jobMessage->getId();

        $job = $this->jobRepository->getJobById($id);

        if (null === $job) {
            throw new UnrecoverableMessageHandlingException(sprintf('Job (%d) not found', $id));
        }

        $this->jobService->executeJob($job);
    }
}
