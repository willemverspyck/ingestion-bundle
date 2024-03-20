<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Listener;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Spyck\IngestionBundle\Entity\TimestampInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class TimestampListener
{
    public function prePersist(PrePersistEventArgs $prePersistEventArgs): void
    {
        $object = $prePersistEventArgs->getObject();

        if ($object instanceof TimestampInterface) {
            $object->setTimestampCreate(new DateTimeImmutable());
        }
    }

    public function preUpdate(PreUpdateEventArgs $preUpdateEventArgs): void
    {
        $object = $preUpdateEventArgs->getObject();

        if ($object instanceof TimestampInterface) {
            $object->setTimestampUpdate(new DateTimeImmutable());
        }
    }
}
