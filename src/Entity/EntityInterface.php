<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Entity;

interface EntityInterface
{
    public static function getIngestionEntity(): self;

    public function getIngestionLog(): ?Log;

    public function setIngestionLog(?Log $log): self;
}
