<?php

namespace Spyck\IngestionBundle\Service;

use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FileService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * Download image and store in temporary directory.
     *
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function handleFile(?string $url, string $name): ?UploadedFile
    {
        if (null === $url) {
            return null;
        }

        $file = tempnam(sys_get_temp_dir(), 'user');

        $response = $this->httpClient->request('GET', $url);

        try {
            $content = $response->getContent();
        } catch (Exception) {
            return null;
        }

        if (false === file_put_contents($file, $content)) {
            return null;
        }

        return new UploadedFile($file, $name, null, null, true);
    }
}
