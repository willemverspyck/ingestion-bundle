<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Service;

use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Spyck\IngestionBundle\Entity\EntityInterface;
use Spyck\IngestionBundle\Entity\Job;
use Spyck\IngestionBundle\Entity\Source;
use Spyck\IngestionBundle\Message\SourceMessage;
use Spyck\IngestionBundle\Repository\JobRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Twig\Error\LoaderError;

class SourceService
{
    public function __construct(private readonly DataService $dataService, private readonly JobRepository $jobRepository, private readonly JobService $jobService, private readonly LoggerInterface $logger, private readonly MessageBusInterface $messageBus)
    {
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws TransportExceptionInterface
     * @throws LoaderError
     */
    public function executeSource(Source $source): void
    {
        $adapter = $source->getModule()->getAdapter();

        if (false === class_exists($adapter)) {
            throw new Exception(sprintf('Class "%s" not found', $adapter));
        }

        if (false === is_a($adapter, EntityInterface::class, true)) {
            throw new Exception(sprintf('Class "%s" is not instance of "%s"', $adapter, EntityInterface::class));
        }

        $data = $this->dataService->getData($source->getUrl(), $source->getType());

        if (null === $data) {
            throw new Exception(sprintf('Data not found for "%s"', $source->getName()));
        }

        $this->jobRepository->patchJobActiveBySource(source: $source, active: null);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if (null !== $source->getPath()) {
            $data = $propertyAccessor->getValue($data, $source->getPath());
        }

        array_walk($data, function (array $data) use ($source): void {
            $job = $this->getJob($source, $data);

            if (null === $job) {
                return;
            }

            $this->jobService->executeJobAsMessage($job);
        });

        $jobs = $this->jobRepository->getJobsBySourceAndActiveIsNull($source);

        foreach ($jobs as $job) {
            $this->jobRepository->patchJob(job: $job, fields: ['active'], active: false);

            $this->jobService->executeJobAsMessage($job);
        }
    }

    public function executeSourceAsMessage(Source $source): void
    {
        $sourceMessage = new SourceMessage();
        $sourceMessage->setId($source->getId());

        $this->messageBus->dispatch($sourceMessage);
    }

    /**
     * @return array|int|float|string|null
     */
    private function getCode(Source $source, array $data)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $value = $propertyAccessor->getValue($data, $source->getCode());

        if (is_array($value)) {
            return null;
        }

        return $value;
    }

    private function getJob(Source $source, array $data): ?Job
    {
        $code = $this->getCode($source, $data);

        if (null === $code) {
            $this->logger->error(sprintf('Code not found (%s)', $source->getName()), $data);

            return null;
        }

        $job = $this->jobRepository->getJobBySourceAndCode($source, $code);

        if (null === $job) {
            return $this->jobRepository->putJob(source: $source, code: $code, data: $data);
        }

        $this->jobRepository->patchJob(job: $job, fields: ['data', 'messages', 'processed', 'active'], data: $data, processed: false, active: true);

        return $job;
    }
}
