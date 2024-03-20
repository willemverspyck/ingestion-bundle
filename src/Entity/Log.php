<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;
use Spyck\IngestionBundle\Repository\LogRepository;

#[Doctrine\Entity(repositoryClass: LogRepository::class)]
#[Doctrine\Table(name: 'ingestion_log')]
#[Doctrine\UniqueConstraint(columns: ['source_id', 'code'])]
class Log extends AbstractTimestamp
{
    #[Doctrine\Column(name: 'id', type: Types::INTEGER, options: ['unsigned' => true])]
    #[Doctrine\GeneratedValue(strategy: 'IDENTITY')]
    #[Doctrine\Id]
    private ?int $id = null;

    #[Doctrine\JoinColumn(name: 'source_id', referencedColumnName: 'id', nullable: false)]
    #[Doctrine\ManyToOne(targetEntity: Source::class, inversedBy: 'maps')]
    private Source $source;

    #[Doctrine\Column(name: 'code', type: Types::STRING, length: 128)]
    private string $code;

    #[Doctrine\Column(name: 'data', type: Types::JSON, nullable: true)]
    private ?array $data = null;

    #[Doctrine\Column(name: 'messages', type: Types::JSON, nullable: true)]
    private ?array $messages = null;

    #[Doctrine\Column(name: 'processed', type: Types::BOOLEAN)]
    private bool $processed;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function setSource(Source $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getMessages(): ?array
    {
        return $this->messages;
    }

    public function setMessages(?array $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): self
    {
        $this->processed = $processed;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->getSource()->getName(), $this->getCode());
    }
}
