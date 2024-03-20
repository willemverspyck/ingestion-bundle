<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;

abstract class AbstractTimestamp implements TimestampInterface
{
    #[Doctrine\Column(name: 'timestamp_create', type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $timestampCreate;

    #[Doctrine\Column(name: 'timestamp_update', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $timestampUpdate = null;

    public function getTimestampCreate(): DateTimeImmutable
    {
        return $this->timestampCreate;
    }

    public function setTimestampCreate(DateTimeImmutable $date): void
    {
        $this->timestampCreate = $date;
    }

    public function getTimestampUpdate(): ?DateTimeImmutable
    {
        return $this->timestampUpdate;
    }

    public function setTimestampUpdate(DateTimeImmutable $date): void
    {
        $this->timestampUpdate = $date;
    }
}
