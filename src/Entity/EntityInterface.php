<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Entity;

interface EntityInterface
{
    public function getLog(): ?Log;

    public function setLog(?Log $log): self;
}
