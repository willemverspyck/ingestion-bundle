<?php

namespace Spyck\IngestionBundle\Message;

interface SourceMessageInterface
{
    public function setId(int $id): void;

    public function getId(): int;
}
