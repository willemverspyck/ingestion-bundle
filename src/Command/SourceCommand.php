<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Command;

use Spyck\IngestionBundle\Entity\Source;
use Spyck\IngestionBundle\Repository\SourceRepository;
use Spyck\IngestionBundle\Service\SourceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'spyck:ingestion:source', description: 'Index sources')]
final class SourceCommand extends Command
{
    public function __construct(private readonly SourceRepository $sourceRepository, private readonly SourceService $sourceService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Identifier of the source');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getOption('id');

        if (null === $id) {
            $sources = $this->sourceRepository->getSourceData();

            foreach ($sources as $source) {
                $this->executeSource($source, $output);
            }

            return Command::SUCCESS;
        }

        $source = $this->sourceRepository->getSourceById((int) $id);

        if (null === $source) {
            $output->writeln(sprintf('Source "%d" not found', $id));

            return Command::FAILURE;
        }

        $this->executeSource($source, $output);

        return Command::SUCCESS;
    }

    private function executeSource(Source $source, OutputInterface $output): void
    {
        $output->writeln(sprintf('Source "%s"', $source->getName()));

        $this->sourceService->handleSource($source);
    }
}
