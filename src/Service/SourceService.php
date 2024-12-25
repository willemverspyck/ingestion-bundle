<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Service;

use Exception;
use Psr\Cache\InvalidArgumentException;
use Spyck\IngestionBundle\Entity\EntityInterface;
use Spyck\IngestionBundle\Entity\Source;
use Spyck\IngestionBundle\Message\SourceMessage;
use Spyck\IngestionBundle\Repository\LogRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Twig\Error\LoaderError;

class SourceService
{
    public function __construct(private readonly ClientService $clientService, private readonly ContentService $contentService, private readonly LogRepository $logRepository, private readonly MessageBusInterface $messageBus)
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

        $data = $this->clientService->getData($source->getUrl(), $source->getType());

        if (null === $data) {
            throw new Exception(sprintf('Data not found for "%s"', $source->getName()));
        }

        $this->logRepository->patchLogProcessedBySource($source, false);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if (null !== $source->getPath()) {
            $data = $propertyAccessor->getValue($data, $source->getPath());
        }

        foreach ($data as $row) {
            $this->contentService->executeContentAsMessage($source, $row);
        }
    }

    public function executeSourceAsMessage(Source $source): void
    {
        $sourceMessage = new SourceMessage();
        $sourceMessage->setId($source->getId());

        $this->messageBus->dispatch($sourceMessage);
    }
}
