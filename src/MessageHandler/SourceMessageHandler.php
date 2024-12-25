<?php

namespace Spyck\IngestionBundle\MessageHandler;

use Spyck\IngestionBundle\Entity\Source;
use Spyck\IngestionBundle\Message\SourceMessageInterface;
use Spyck\IngestionBundle\Repository\SourceRepository;
use Spyck\IngestionBundle\Service\SourceService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
final class SourceMessageHandler extends AbstractMessageHandler
{
    public function __construct(private readonly SourceRepository $sourceRepository, private readonly SourceService $sourceService)
    {
    }

    public function __invoke(SourceMessageInterface $sourceMessage): void
    {
        $source = $this->getSourceById($sourceMessage->getId());

        $this->sourceService->executeSource($source);
    }

    private function getSourceById(int $id): Source
    {
        $source = $this->sourceRepository->getSourceById($id);

        if (null === $source) {
            throw new UnrecoverableMessageHandlingException(sprintf('Source (%d) not found', $id));
        }

        return $source;
    }
}
