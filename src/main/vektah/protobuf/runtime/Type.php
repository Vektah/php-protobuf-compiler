<?php

namespace vektah\protobuf\runtime;

use RuntimeException;

class Type
{
    const _MIN = 0;
    const DOUBLE = 1;
    const FLOAT = 2;
    const INT32 = 3;
    const INT64 = 4;
    const SINT32 = 5;
    const SINT64 = 6;
    const FIXED32 = 7;
    const FIXED64 = 8;
    const SFIXED32 = 9;
    const SFIXED64 = 10;
    const BOOL = 11;
    const STRING = 12;
    const BYTES = 13;
    const _MAX = 14;

    public static function isValid($type)
    {
        return is_string($type) && class_exists($type) || (is_int($type) && $type > self::_MIN && $type < self::_MAX);
    }

    private static function describeType($thing) {
        if (is_object($thing)) {
            return get_class($thing);
        }

        return gettype($thing);
    }

    public static function sanitizeRepeated($type, $value) {
        if (!is_array($value)) {
            throw new RuntimeException(self::describeType($value) . " is not an array. Repeated elements must be an array.");
        }
        $result = [];

        foreach ($value as $repeated_value) {
            $result[] = self::sanitize($type, $repeated_value);
        }

        return $result;
    }

    public static function sanitize($type, $value)
    {
        if (is_string($type)) {
            if ($value !== null && !($value instanceof $type)) {
                throw new RuntimeException(self::describeType($value) . " is not a $type");
            }
            return $value;
        }

        if (is_null($value)) {
            return null;
        }

        switch ($type) {
            case self::DOUBLE:
            case self::FLOAT:
                if (!is_numeric($value)) {
                    throw new RuntimeException(self::describeType($value) . " is not numeric");
                }
                return (float)$value;

            case self::INT32:
            case self::INT64:
            case self::SINT32:
            case self::SINT64:
            case self::FIXED32:
            case self::FIXED64:
            case self::SFIXED32:
            case self::SFIXED64:
                if (!is_numeric($value)) {
                    throw new RuntimeException(self::describeType($value) . " is not numeric");
                }
                return (int)$value;

            case self::BOOL:
                return (bool)$value;

            case self::STRING:
                return (string)$value;

            case self::BYTES:
                return (string)$value;
            default:
                throw new \RuntimeException("type $type is not valid");
        }
    }
}
