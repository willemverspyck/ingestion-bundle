<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Normalizer;

use Spyck\IngestionBundle\Service\FileService;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class FileNormalizer implements DenormalizerInterface
{
    public function __construct(private FileService $fileService)
    {
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (false === array_key_exists(AbstractNormalizer::KEY, $context) || false === $context[AbstractNormalizer::KEY]) {
            return false;
        }

        return File::class === $type;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ?object
    {
        if (false === is_string($data)) {
            return null;
        }

        $path = parse_url($data, PHP_URL_PATH);

        if (false === $path) {
            return null;
        }

        $imageType = exif_imagetype($data);

        if (false === $imageType) {
            return null;
        }

        $extension = image_type_to_extension($imageType, false);

        if (false === $extension) {
            return false;
        }

        $name = pathinfo($path, PATHINFO_FILENAME);

        return $this->fileService->getFile($data, sprintf('%s.%s', $name, $extension));
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => __CLASS__ === static::class,
        ];
    }
}
