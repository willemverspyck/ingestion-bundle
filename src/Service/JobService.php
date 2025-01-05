<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Spyck\IngestionBundle\Entity\Job;
use Spyck\IngestionBundle\Entity\Map;
use Spyck\IngestionBundle\Event\PostJobEvent;
use Spyck\IngestionBundle\Event\PreJobEvent;
use Spyck\IngestionBundle\Message\JobMessage;
use Spyck\IngestionBundle\Normalizer\AbstractNormalizer as IngestionAbstractNormalizer;
use Spyck\IngestionBundle\Repository\JobRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;
use Twig\Error\RuntimeError;

class JobService
{
    public function __construct(private readonly ClientService $clientService, private readonly DenormalizerInterface $denormalizer, private readonly EntityManagerInterface $entityManager, private readonly EntityService $entityService, private readonly Environment $environment, private readonly EventDispatcherInterface $eventDispatcher, private readonly JobRepository $jobRepository, private readonly MessageBusInterface $messageBus, private readonly ValidatorInterface $validator)
    {
    }

    public function executeJob(Job $job): void
    {
        $preJobEvent = new PreJobEvent($job);

        $this->eventDispatcher->dispatch($preJobEvent);

        if ($job->isActive()) {
            $source = $job->getSource();
            $data = $job->getData();

            if (null !== $source->getCodeUrl()) {
                $codeUrl = $this->getTemplate($source->getCodeUrl(), $data);
                $codeRow = $this->clientService->getData($codeUrl, $source->getType());

                $data = array_merge($data, $codeRow);

                $this->jobRepository->patchJob(job: $job, fields: ['data'], data: $data);
            }

            $this->putContent($job, $data);
        }

        $postJobEvent = new PostJobEvent($job);

        $this->eventDispatcher->dispatch($postJobEvent);
    }

    public function executeJobAsMessage(Job $job): void
    {
        $jobMessage = new JobMessage();
        $jobMessage->setId($job->getId());

        $this->messageBus->dispatch($jobMessage);
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

    private function getTemplate(string $template, array $data): string
    {
        try {
            return $this->environment->createTemplate($template, 'template')->render($data);
        } catch (RuntimeError $runtimeError) {
            throw new Exception($runtimeError->getMessage());
        }
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

    private function putContent(Job $job, array $data): void
    {
        $content = [];

        $source = $job->getSource();

        $maps = $source->getMaps();

        foreach ($maps as $map) {
            $value = $this->getContent($map, $data);

            $content = $this->setContent($content, explode('.', $map->getField()->getCode()), $value);
        }

        foreach ($maps as $map) {
            $field = $map->getField();

            if (null !== $map->getTemplate()) {
                $content = $this->setContent($content, explode('.', $field->getCode()), $this->getTemplate($map->getTemplate(), $data));
            }
        }

        $adapter = $source->getModule()->getAdapter();

        $entity = $this->entityService->getEntityByJob($job);

        if (null === $entity) {
            $entity = $adapter::getIngestionEntity();
        } else {
            foreach ($source->getMaps() as $map) {
                $field = $map->getField();

                if (false === $map->isValueUpdate()) {
                    $content = $this->removeContent($content, explode('.', $field->getCode()));
                }
            }

            // This next "foreach" is for not supporting arrays of objects. From Symfony: When the AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE option is set to true, existing children of the root OBJECT_TO_POPULATE are updated from the normalized data, instead of the denormalizer re-creating them. Note that DEEP_OBJECT_TO_POPULATE only works for single child objects, but not for arrays of objects. Those will still be replaced when present in the normalized data.
            foreach ($this->entityManager->getClassMetadata(get_class($entity))->getAssociationMappings() as $mapping) {
                $field = $mapping['fieldName'];

                if (ClassMetadata::ONE_TO_MANY === $mapping['type'] && array_key_exists($field, $content)) {
                    $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                        ->enableExceptionOnInvalidIndex()
                        ->getPropertyAccessor();

                    $objects = $propertyAccessor->getValue($entity, $field);

                    foreach ($objects as $object) {
                        if (count($content[$field]) > 0) {
                            $contentObject = array_shift($content[$field]);

                            $this->denormalizer->denormalize($contentObject, $mapping['targetEntity'], null, [IngestionAbstractNormalizer::KEY => true, AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true, AbstractNormalizer::OBJECT_TO_POPULATE => $object]);
                        }
                    }

                    // Add collections if there are more than the original object
                    foreach ($content[$field] as $contentObject) {
                        $object = $this->denormalizer->denormalize($contentObject, $mapping['targetEntity'], null, [IngestionAbstractNormalizer::KEY => true, AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]);

                        $fieldSet = sprintf('add%s', ucfirst(substr($field, 0, -1)));

                        $entity->$fieldSet($object);
                    }

                    unset($content[$field]);
                }
            }
        }

        try {
            $context = [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                AbstractNormalizer::OBJECT_TO_POPULATE => $entity,
                IngestionAbstractNormalizer::KEY => true,
            ];

            $entity = $this->denormalizer->denormalize($content, $adapter, null, $context);
        } catch (NotNormalizableValueException $exception) {
            $messages = [
                $exception->getMessage(),
            ];

            $this->jobRepository->patchJob(job: $job, fields: ['messages', 'processed'], messages: $messages, processed: false);

            return;
        }

        $violations = $this->validator->validate($entity, null, ['Default']);

        if ($violations->count() > 0) {
            $messages = [];

            foreach ($violations->getIterator() as $violation) {
                $field = $violation->getPropertyPath();

                if (null !== $field) {
                    $messages[$field] = $violation->getMessage();
                }
            }

            $this->jobRepository->patchJob(job: $job, fields: ['messages', 'processed'], messages: $messages, processed: false);

            return;
        }

        $entity->setIngestionJob($job);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->jobRepository->patchJob(job: $job, fields: ['processed'], processed: true);
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
}
