<?php

namespace Spyck\IngestionBundle\Message;

interface ContentMessageInterface
{
    public function setId(int $id): void;

    public function getId(): int;

    public function setData(array $data): void;

    public function getData(): array;
}
