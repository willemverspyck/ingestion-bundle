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

        $match = [];

        foreach ($simpleXMLElement->children() as $child) {
            $key = $child->getName();

            $match[$key] = array_key_exists($key, $match) ? $match[$key] + 1 : 1;
        }

        foreach ($simpleXMLElement->children() as $child) {
            $key = $child->getName();
            $node = $child->count() > 0 ? self::simpleXmlToArray($child) : $child->__toString();

            if ($match[$key] > 1) {
                if (false === array_key_exists($key, $data)) {
                    $data[$key] = [];
                }

                $data[$key][] = $node;
            } else {
                $data[$key] = $node;
            }
        }

        return $data;
    }
}
