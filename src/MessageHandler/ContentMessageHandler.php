<?php

namespace Spyck\IngestionBundle\MessageHandler;

use Spyck\IngestionBundle\Entity\Source;
use Spyck\IngestionBundle\Message\ContentMessageInterface;
use Spyck\IngestionBundle\Repository\SourceRepository;
use Spyck\IngestionBundle\Service\ContentService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
final class ContentMessageHandler extends AbstractMessageHandler
{
    public function __construct(private readonly SourceRepository $sourceRepository, private readonly ContentService $contentService)
    {
    }

    public function __invoke(ContentMessageInterface $contentMessage): void
    {
        $source = $this->getSourceById($contentMessage->getId());

        $this->contentService->executeContent($source, $contentMessage->getData());
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
