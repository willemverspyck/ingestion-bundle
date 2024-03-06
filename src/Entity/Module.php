<?php

namespace Spyck\IngestionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;

#[Doctrine\Entity]
#[Doctrine\Table(name: 'ingestion_module')]
class Module
{
    #[Doctrine\Column(name: 'id', type: Types::SMALLINT, options: ['unsigned' => true])]
    #[Doctrine\GeneratedValue(strategy: 'IDENTITY')]
    #[Doctrine\Id]
    private ?int $id = null;

    #[Doctrine\Column(name: 'name', type: Types::STRING, length: 128)]
    private string $name;

    #[Doctrine\Column(name: 'adapter', type: Types::STRING, length: 128)]
    private string $adapter;

    #[Doctrine\Column(name: 'active', type: Types::BOOLEAN)]
    private bool $active;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAdapter(): string
    {
        return $this->adapter;
    }

    public function setAdapter(string $adapter): self
    {
        $this->adapter = $adapter;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
