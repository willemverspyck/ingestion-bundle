<?php

namespace Spyck\IngestionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;

#[Doctrine\Entity]
#[Doctrine\Table(name: 'ingestion_map')]
class Map
{
    #[Doctrine\Column(name: 'id', type: Types::INTEGER, options: ['unsigned' => true])]
    #[Doctrine\GeneratedValue(strategy: 'IDENTITY')]
    #[Doctrine\Id]
    private ?int $id = null;

    #[Doctrine\JoinColumn(name: 'source_id', referencedColumnName: 'id', nullable: false)]
    #[Doctrine\ManyToOne(targetEntity: Source::class, inversedBy: 'maps')]
    private Source $source;

    #[Doctrine\JoinColumn(name: 'field_id', referencedColumnName: 'id', nullable: false)]
    #[Doctrine\ManyToOne(targetEntity: Field::class)]
    private Field $field;

    #[Doctrine\Column(name: 'path', type: Types::TEXT, nullable: true)]
    private ?string $path = null;

    #[Doctrine\Column(name: 'code', type: Types::STRING, length: 128, nullable: true)]
    private ?string $code = null;

    #[Doctrine\Column(name: 'value', type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    #[Doctrine\Column(name: 'value_update', type: Types::BOOLEAN)]
    private bool $valueUpdate;

    #[Doctrine\Column(name: 'template', type: Types::TEXT, nullable: true)]
    private ?string $template = null;

    #[Doctrine\Column(name: 'translate', type: Types::JSON, nullable: true)]
    private ?array $translate = null;

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

    public function getField(): Field
    {
        return $this->field;
    }

    public function setField(Field $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function isValueUpdate(): bool
    {
        return $this->valueUpdate;
    }

    public function setValueUpdate(bool $valueUpdate): self
    {
        $this->valueUpdate = $valueUpdate;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getTranslate(): ?array
    {
        return $this->translate;
    }

    public function setTranslate(?array $translate): self
    {
        $this->translate = $translate;

        return $this;
    }

    public function __toString(): string
    {
        if (null === $this->getCode()) {
            return '-';
        }

        return $this->getCode();
    }
}
