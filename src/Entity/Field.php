<?php

namespace Spyck\IngestionBundle\Entity;

use Spyck\IngestionBundle\Repository\FieldRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;

#[Doctrine\Entity(repositoryClass: FieldRepository::class)]
#[Doctrine\Table(name: 'ingestion_field')]
class Field
{
    #[Doctrine\Column(name: 'id', type: Types::SMALLINT, options: ['unsigned' => true])]
    #[Doctrine\GeneratedValue(strategy: 'IDENTITY')]
    #[Doctrine\Id]
    private ?int $id = null;

    #[Doctrine\JoinColumn(name: 'module_id', referencedColumnName: 'id', nullable: false)]
    #[Doctrine\ManyToOne(targetEntity: Module::class)]
    private Module $module;

    #[Doctrine\Column(name: 'name', type: Types::STRING, length: 128)]
    private string $name;

    #[Doctrine\Column(name: 'code', type: Types::STRING, length: 128)]
    private string $code;

    #[Doctrine\Column(name: 'multiple', type: Types::BOOLEAN)]
    private bool $multiple;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModule(): Module
    {
        return $this->module;
    }

    public function setModule(Module $module): self
    {
        $this->module = $module;

        return $this;
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
