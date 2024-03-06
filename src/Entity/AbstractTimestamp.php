<?php

namespace Spyck\IngestionBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;

abstract class AbstractTimestamp implements TimestampInterface
{
    #[Doctrine\Column(name: 'timestamp_create', type: Types::DATETIME_MUTABLE)]
    protected DateTimeInterface $timestampCreate;

    #[Doctrine\Column(name: 'timestamp_update', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $timestampUpdate = null;

    public function getTimestampCreate(): DateTimeInterface
    {
        return $this->timestampCreate;
    }

    public function setTimestampCreate(DateTimeInterface $date): void
    {
        $this->timestampCreate = $date;
    }

    public function getTimestampUpdate(): ?DateTimeInterface
    {
        return $this->timestampUpdate;
    }

    public function setTimestampUpdate(DateTimeInterface $date): void
    {
        $this->timestampUpdate = $date;
    }
}
