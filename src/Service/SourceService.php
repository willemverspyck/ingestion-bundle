<?php

namespace Spyck\IngestionBundle\Service;

use Spyck\IngestionBundle\Entity\Map;
use Spyck\IngestionBundle\Entity\Source;
use Spyck\IngestionBundle\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Spyck\IngestionBundle\Entity\EntityInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;
use Twig\Error\LoaderError;

class SourceService
{
    public function __construct(private readonly CacheInterface $cache, private readonly EntityManagerInterface $entityManager, private readonly Environment $environment, private readonly HttpClientInterface $httpClient, private readonly LogRepository $logRepository, private readonly SerializerInterface $serializer, private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws TransportExceptionInterface
     * @throws LoaderError
     */
    public function handleSource(Source $source, bool $debug): ?array
    {
        $adapter = $source->getModule()->getAdapter();

        if (false === class_exists($adapter)) {
            throw new Exception(sprintf('Class "%s" not found', $adapter));
        }

        if (false === is_a($adapter, EntityInterface::class, true)) {
            throw new Exception(sprintf('Class "%s" is not instance of "%s"', $adapter, EntityInterface::class));
        }

        $data = $this->getData($source->getUrl(), $source->getType());

        if (null === $data) {
            return [
                'Data not found',
            ];
        }

        $this->logRepository->patchLogProcessedBySource($source, false);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if (null !== $source->getPath()) {
            $data = $propertyAccessor->getValue($data, $source->getPath());
        }

        foreach ($data as $row) {
            if ($debug) {
                dump($row);
            }

            $key = $this->getKey($source, $row);

            if (null !== $key) {
                if (null !== $source->getCodeUrl()) {
                    $template = $this->environment->createTemplate($source->getCodeUrl());

                    $codeUrl = $template->render($row);
                    $codeRow = $this->getData($codeUrl, $source->getType());

                    $row = array_merge($row, $codeRow);
                }

                $content = [];
                $files = [];

                $maps = $source->getMaps();

                foreach ($maps as $map) {
                    $value = $this->getContent($map, $row);

                    $code = $map->getField()->getCode();

                    $content = $this->setContent($content, explode('.', $code), $value);
                }

                foreach ($maps as $map) {
                    $field = $map->getField();

                    if (null !== $map->getTemplate()) {
                        $template = $this->environment->createTemplate($map->getTemplate());

                        $content = $this->setContent($content, explode('.', $field->getCode()), $template->render($row));
                    }
                }

                if ($debug) {
                    dump($content);
                }

                $log = $this->logRepository->getLogBySourceAndCode($source, $key);

                if (null === $log) {
                    $entity = null;

                    $log = $this->logRepository->putLog(source: $source, code: $key, data: $content);
                } else {
                    $this->logRepository->patchLog(log: $log, fields: ['data', 'messages'], data: $content);

                    /** RepositoryInterface $repository */
                    $repository = $this->entityManager->getRepository($adapter);

                    $entity = $repository->createQueryBuilder('entity')
                        ->where('entity.log = :log')
                        ->setParameter('log', $log)
                        ->getQuery()
                        ->getOneOrNullResult();

                    foreach ($maps as $map) {
                        $field = $map->getField();

                        if (false === $map->isValueUpdate()) {
                            $content = $this->removeContent($content, explode('.', $field->getCode()));
                            $files = $this->removeContent($files, explode('.', $field->getCode()));
                        }
                    }
                }

                if (null !== $entity) {
                    // This next "foreach" is for not supporting arrays of objects. From Symfony: When the AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE option is set to true, existing children of the root OBJECT_TO_POPULATE are updated from the normalized data, instead of the denormalizer re-creating them. Note that DEEP_OBJECT_TO_POPULATE only works for single child objects, but not for arrays of objects. Those will still be replaced when present in the normalized data.
                    foreach ($this->entityManager->getClassMetadata(get_class($entity))->getAssociationMappings() as $mapping) {
                        $field = $mapping['fieldName'];

                        if (ClassMetadataInfo::ONE_TO_MANY === $mapping['type'] && array_key_exists($field, $content)) {
                            $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                                ->enableExceptionOnInvalidIndex()
                                ->getPropertyAccessor();

                            $objects = $propertyAccessor->getValue($entity, $field);

                            foreach ($objects as $object) {
                                if (count($content[$field]) > 0) {
                                    $contentObject = array_shift($content[$field]);

                                    $this->serializer->deserialize(json_encode($contentObject), $mapping['targetEntity'], 'json', ['SOURCE_IMPORT' => true, AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true, AbstractNormalizer::OBJECT_TO_POPULATE => $object]);
                                }
                            }

                            // Add locations if there are more than 1
                            foreach ($content[$field] as $contentObject) {
                                $object = $this->serializer->deserialize(json_encode($contentObject), $mapping['targetEntity'], 'json', ['SOURCE_IMPORT' => true, AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]);

                                $fieldSet = sprintf('add%s', ucfirst(substr($field, 0, -1)));

                                $entity->$fieldSet($object);
                            }

                            unset($content[$field]);
                        }
                    }
                }

                $context = [
                    'SOURCE_IMPORT' => true,
                    AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                ];

                if (null !== $entity) {
                    $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $entity;
                }

                $entity = $this->serializer->deserialize(json_encode($content), $adapter, 'json', $context);

                $violations = $this->validator->validate($entity, null, ['Default']);

                if ($violations->count() > 0) {
                    $messages = [];

                    foreach ($violations->getIterator() as $violation) {
                        $field = $violation->getPropertyPath();

                        if (null !== $field) {
                            $messages[$field] = $violation->getMessage();
                        }
                    }

                    $this->logRepository->patchLog(log: $log, fields: ['messages'], messages: $messages);
                } else {
                    if ($debug) {
                        dump($entity);
                    } else {
                        $entity->setLog($log);

                        $this->entityManager->persist($entity);
                        $this->entityManager->flush();

                        $this->logRepository->patchLog($log, ['processed'], processed: true);
                    }
                }
            } else {
                dump(sprintf('"Primary key" not found (%s)', $source->getCode()));

                if ($debug) {
                    dump($row);
                }
            }
        }

        return [];
    }

    private function setContent(array $data, array $fields, mixed $value): array
    {
        $count = count($fields);

        if (0 === $count) {
            return $data;
        }

        $field = array_shift($fields);

        if (1 === $count) {
            $data[$field] = $value;

            return $data;
        }

        $data[$field] = $this->setContent(array_key_exists($field, $data) ? $data[$field] : [], $fields, $value);

        return $data;
    }

    private function removeContent(array $data, array $fields): array
    {
        $count = count($fields);

        if (0 === $count) {
            return $data;
        }

        $field = array_shift($fields);

        if (1 === $count) {
            unset($data[$field]);

            return $data;
        }

        $data[$field] = $this->removeContent(array_key_exists($field, $data) ? $data[$field] : [], $fields);

        if (0 === count($data[$field])) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getData(string $url, string $type): ?array
    {
        $key = md5($url);

        return $this->cache->get($key, function (ItemInterface $item) use ($url, $type): ?array {
            $item->expiresAfter(3600);

            $response = $this->httpClient->request('GET', $url);

            if (Source::TYPE_JSON === $type) {
                return $response->toArray(false);
            }

            if (Source::TYPE_XML === $type) {
                $data = simplexml_load_string($response->getContent(false));

                if (false === $data) {
                    throw new Exception('XML not welformed');
                }

                $data = json_encode($data);

                if (false === $data) {
                    throw new Exception('XML not welformed');
                }

                return json_decode($data, true);
            }

            throw new Exception('Type not found');
        });
    }

    /**
     * @return array|int|float|string|null
     */
    private function getKey(Source $source, array $data)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $value = $propertyAccessor->getValue($data, $source->getCode());

        if (is_array($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @return array|int|float|string
     */
    private function getContent(Map $map, array $data)
    {
        if (null === $map->getPath()) {
            return $this->getValue($map, $data);
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $values = $propertyAccessor->getValue($data, $map->getPath());

        $content = [];

        if (is_array($values)) {
            foreach ($values as $value) {
                $content[] = $this->getValue($map, $value);
            }
        }

        return $content;
    }

    /**
     * @return array|int|float|string
     */
    private function getValue(Map $map, array $data)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $value = null;

        if (null !== $map->getCode()) {
            $value = $propertyAccessor->getValue($data, $map->getCode());
        }

        if (null !== $value) {
            $field = $map->getField();

            if ($field->isMultiple()) {
                if (false === is_array($value)) {
                    $value = [$value];
                }
            } else {
                if (is_array($value)) {
                    $value = array_shift($value);
                }
            }
        }

        if (null === $value || (is_string($value) && 0 === strlen($value))) {
            $value = null === $map->getValue() ? null : json_decode($map->getValue(), true);
        }

        $translate = $map->getTranslate();

        if (null === $translate) {
            return $value;
        }

        if (is_array($value)) {
            $returnValue = [];

            foreach ($value as $val) {
                if (array_key_exists($val, $translate)) {
                    $returnValue[] = $translate[$val];
                }
            }

            return array_unique($returnValue);
        }

        if (array_key_exists($value, $translate)) {
            return $translate[$value];
        }

        return $value;
    }
}
