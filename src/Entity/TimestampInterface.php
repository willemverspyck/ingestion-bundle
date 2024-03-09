<?php

namespace Spyck\IngestionBundle\Entity;

use DateTimeImmutable;

interface TimestampInterface
{
    public function getTimestampCreate(): DateTimeImmutable;

    public function setTimestampCreate(DateTimeImmutable $date): void;

    public function getTimestampUpdate(): ?DateTimeImmutable;

    public function setTimestampUpdate(DateTimeImmutable $date): void;
}
