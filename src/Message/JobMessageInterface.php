<?php

namespace Spyck\IngestionBundle\Message;

interface JobMessageInterface
{
    public function getId(): int;

    public function setId(int $id): void;
}
