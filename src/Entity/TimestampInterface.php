<?php

namespace Spyck\IngestionBundle\Entity;

use DateTimeInterface;

interface TimestampInterface
{
    public function getTimestampCreate(): DateTimeInterface;

    public function setTimestampCreate(DateTimeInterface $date): void;

    public function getTimestampUpdate(): ?DateTimeInterface;

    public function setTimestampUpdate(DateTimeInterface $date): void;
}
