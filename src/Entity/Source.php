<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;
use Spyck\IngestionBundle\Repository\SourceRepository;

#[Doctrine\Entity(repositoryClass: SourceRepository::class)]
#[Doctrine\Table(name: 'ingestion_source')]
class Source
{
    public const TYPE_JSON = 'json';
    public const TYPE_JSON_NAME = 'JSON';
    public const TYPE_XML = 'xml';
    public const TYPE_XML_NAME = 'XML';

    #[Doctrine\Column(name: 'id', type: Types::INTEGER, options: ['unsigned' => true])]
    #[Doctrine\GeneratedValue(strategy: 'IDENTITY')]
    #[Doctrine\Id]
    private ?int $id = null;

    #[Doctrine\JoinColumn(name: 'module_id', referencedColumnName: 'id', nullable: false)]
    #[Doctrine\ManyToOne(targetEntity: Module::class)]
    private Module $module;

    #[Doctrine\Column(name: 'name', type: Types::STRING, length: 128)]
    private string $name;

    #[Doctrine\Column(name: 'url', type: Types::TEXT)]
    private string $url;

    #[Doctrine\Column(name: 'type', type: Types::STRING, length: 8)]
    private string $type;

    #[Doctrine\Column(name: 'path', type: Types::TEXT, nullable: true)]
    private ?string $path = null;

    #[Doctrine\Column(name: 'code', type: Types::STRING, length: 128)]
    private string $code;

    #[Doctrine\Column(name: 'code_url', type: Types::TEXT, nullable: true)]
    private ?string $codeUrl = null;

    /**
     * @var Collection<int, Map>
     */
    #[Doctrine\OneToMany(mappedBy: 'source', targetEntity: Map::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $maps;

    #[Doctrine\Column(name: 'active', type: Types::BOOLEAN)]
    private bool $active;

    public function __construct()
    {
        $this->maps = new ArrayCollection();
    }

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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCodeUrl(): ?string
    {
        return $this->codeUrl;
    }

    public function setCodeUrl(?string $codeUrl): self
    {
        $this->codeUrl = $codeUrl;

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

    /**
     * Get map.
     *
     * @return Collection<int, Map>
     */
    public function getMaps(): Collection
    {
        return $this->maps;
    }

    /**
     * Add map.
     */
    public function addMap(Map $map): self
    {
        $map->setSource($this);

        $this->maps->add($map);

        return $this;
    }

    /**
     * Remove map.
     */
    public function removeMap(Map $map): void
    {
        $this->maps->removeElement($map);
    }

    public static function getTypes(bool $inverse = false): array
    {
        $data = [
            self::TYPE_JSON => self::TYPE_JSON_NAME,
            self::TYPE_XML => self::TYPE_XML_NAME,
        ];

        if ($inverse) {
            return array_flip($data);
        }

        return $data;
    }

    public function __clone()
    {
        $this->id = null;

        $this->setName(sprintf('%s (Copy)', $this->getName()));
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
