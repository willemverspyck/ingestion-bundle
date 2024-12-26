<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Doctrine;

trait TimestampTrait
{
    #[Doctrine\Column(name: 'timestamp_created', type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $timestampCreated;

    #[Doctrine\Column(name: 'timestamp_updated', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $timestampUpdated = null;

    public function getTimestampCreated(): DateTimeImmutable
    {
        return $this->timestampCreated;
    }

    public function setTimestampCreated(DateTimeImmutable $date): void
    {
        $this->timestampCreated = $date;
    }

    public function getTimestampUpdated(): ?DateTimeImmutable
    {
        return $this->timestampUpdated;
    }

    public function setTimestampUpdated(DateTimeImmutable $date): void
    {
        $this->timestampUpdated = $date;
    }
}
