<?php

namespace vektah\protobuf\runtime;

class FieldMetadata
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var int|string oen of the Type::* constants or the name of a class */
    private $type;

    private $is_required;

    private $is_repeated;

    private function __construct() {}

    public static function create($id, $name, $type) {
        $instance = new FieldMetadata();
        assert(is_integer($id));
        assert(is_string($name));
        assert(Type::isValid($type));

        $instance->id = $id;
        $instance->name = $name;
        $instance->type = $type;

        return $instance;
    }

    public function isRequired($required) {
        assert(!$this->is_repeated);
        $this->is_required = $required;

        return $this;
    }

    public function isRepeated($repeated) {
        assert(!$this->is_required);

        $this->is_repeated = $repeated;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function getIsRepeated()
    {
        return $this->is_repeated;
    }

    /**
     * @return bool
     */
    public function getIsRequired()
    {
        return $this->is_required;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int|string
     */
    public function getType()
    {
        return $this->type;
    }
}
