<?php

namespace vektah\protobuf\runtime;

use vektah\common\json\Json;

class Message implements \JsonSerializable
{
    public static function fromJson($json) {
        return self::fromArray(Json::decode($json));
    }

    public static function fromArray(array $data) {
        $class = static::class;
        $instance = new $class();

        foreach ($data as $id => $value) {
            $name = static::getNameForID($id);
            if (!$name) {
                throw new \RuntimeException("Received an unknown field #$id");
            }

            $setter = "set_$name";
            $metadata = static::getMetadata()[$name];
            $type = $metadata->getType();
            if ($value !== null && is_string($type)) {
                if (!is_callable("$type::fromArray")) {
                    throw new \RuntimeException("fromArray does not exist on $type");
                }

                if ($metadata->getIsRepeated()) {
                    $unwrapped = [];

                    if ($value !== null && !is_array($value)) {
                        throw new \RuntimeException("Repeated elements must be an array");
                    }

                    foreach ($value as $v) {
                        $unwrapped[] = $type::fromArray($v);
                    }
                    $value = $unwrapped;
                } else {
                    $value = $type::fromArray($value);
                }
            }

            $instance->$setter($value);
        }

        return $instance;
    }

    public function toJson() {
        return Json::encode($this->toArray());
    }

    public function toArray() {
        $data = [];
        /** @var $metadata FieldMetadata */
        foreach (static::getMetadata() as $name => $metadata) {
            $getter = "get_$name";
            $value = $this->$getter();
            if ($value === null && $metadata->getIsRequired()) {
                throw new \RuntimeException("A value is required for $name");
            }

            if ($value !== null && is_string($metadata->getType())) {
                if ($metadata->getIsRepeated()) {
                    $packed = [];

                    foreach ($value as $v) {
                        $packed[] = $v->toArray();
                    }

                    $value = $packed;
                } else {
                    $value = $value->toArray();
                }
            }

            $data[$metadata->getId()] = $value;
        }

        return $data;
    }

    public function jsonSerialize() {
        return $this->toArray();
    }
}
