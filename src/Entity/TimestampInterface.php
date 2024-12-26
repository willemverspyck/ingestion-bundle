<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Entity;

use DateTimeImmutable;

interface TimestampInterface
{
    public function getTimestampCreated(): DateTimeImmutable;

    public function setTimestampCreated(DateTimeImmutable $date): void;

    public function getTimestampUpdated(): ?DateTimeImmutable;

    public function setTimestampUpdated(DateTimeImmutable $date): void;
}
