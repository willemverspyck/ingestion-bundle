<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Service;

use Exception;
use Psr\Cache\InvalidArgumentException;
use Spyck\IngestionBundle\Entity\Source;
use Spyck\IngestionBundle\Utility\DataUtility;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClientService
{
    public function __construct(private readonly CacheInterface $cache, private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getData(string $url, string $type): ?array
    {
        $key = md5($url);

        return $this->cache->get($key, function (ItemInterface $item) use ($url, $type): ?array {
            $item->expiresAfter(3600);

            $response = $this->httpClient->request(Request::METHOD_GET, $url);

            return match (true) {
                Source::TYPE_JSON === $type => $response->toArray(false),
                Source::TYPE_XML === $type => $this->getXml($response->getContent(false)),
                default => throw new Exception('Type not found'),
            };
        });
    }

    private function getXml(string $content): array
    {
        $data = simplexml_load_string($content);

        if (false === $data) {
            throw new Exception('XML not welformed');
        }

        return DataUtility::simpleXmlToArray($data);
    }
}
