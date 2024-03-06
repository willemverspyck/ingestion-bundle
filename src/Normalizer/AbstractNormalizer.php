<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Normalizer;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

abstract class AbstractNormalizer extends ObjectNormalizer
{
    public const KEY = 'spyck_ingestion_normalizer';
}
