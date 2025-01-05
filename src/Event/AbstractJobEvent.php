<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Event;

use Spyck\IngestionBundle\Entity\Job;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractJobEvent extends Event
{
    public function __construct(private readonly Job $job)
    {
    }

    public function getJob(): Job
    {
        return $this->job;
    }
}
