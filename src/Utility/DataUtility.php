<?php

declare(strict_types=1);

namespace Spyck\IngestionBundle\Utility;

use Exception;
use SimpleXMLElement;
use Throwable;

final class DataUtility
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public static function assert(bool $condition, ?Throwable $throwable = null): void
    {
        if ($condition) {
            return;
        }

        if (null === $throwable) {
            throw new Exception();
        }

        throw $throwable;
    }

    public static function simpleXmlToArray(SimpleXMLElement $simpleXMLElement): array
    {
        $data = [];

        $attributes = [];

        foreach ($simpleXMLElement->attributes() as $attribute) {
            $key = $attribute->getName();

            $attributes[$key] = $attribute->__toString();
        }

        if (count($attributes) > 0) {
            $data['@attributes'] = $attributes;
        }

        foreach ($simpleXMLElement->children() as $child) {
            $key = $child->getName();
            $node = $child->count() > 0 ? self::simpleXmlToArray($child) : $child->__toString();

            if (array_key_exists($key, $data)) {
                if (false === array_key_exists(0, $data[$key])) {
                    $data[$key] = [
                        $data[$key],
                    ];
                }

                $data[$key][] = $node;
            } else {
                $data[$key] = $node;
            }
        }

        return $data;
    }
}
