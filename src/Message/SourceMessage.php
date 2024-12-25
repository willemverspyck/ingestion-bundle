<?php

namespace Spyck\IngestionBundle\Message;

final class SourceMessage implements SourceMessageInterface
{
    private int $id;

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
