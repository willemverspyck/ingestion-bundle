<?php

namespace Spyck\IngestionBundle\Message;

interface SourceMessageInterface
{
    public function getId(): int;

    public function setId(int $id): void;
}
