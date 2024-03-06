<?php

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
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Identifier of the source')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Debug mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getOption('id');
        $debug = $input->getOption('debug');

        if (null === $id) {
            $sources = $this->sourceRepository->getSourceData();

            foreach ($sources as $source) {
                $this->executeSource($source, $debug, $output);
            }

            return Command::SUCCESS;
        }

        $source = $this->sourceRepository->getSourceById($id);

        if (null === $source) {
            $output->writeln(sprintf('Source "%d" not found', $id));

            return Command::FAILURE;
        }

        $this->executeSource($source, $debug, $output);

        return Command::SUCCESS;
    }

    private function executeSource(Source $source, bool $debug, OutputInterface $output): void
    {
        $output->writeln(sprintf('Source "%s"', $source->getName()));

        $errors = $this->sourceService->handleSource($source, $debug);

        if (null === $errors) {
            return;
        }

        foreach ($errors as $error) {
            $output->writeln($error);
        }
    }
}
