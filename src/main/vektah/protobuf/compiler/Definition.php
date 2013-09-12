<?php

namespace vektah\protobuf\compiler;

class Definition
{
    /** @var array */
    public $name;

    /** @var mixed */
    public $definition;

    public function __construct(array $name, $definition = null)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    /**
     * @return string the name of the object itself without any namespace component
     */
    public function getShortName()
    {
        return array_reverse($this->name)[0];
    }

    /**
     * @return string the fully qualified name including the object itself.
     */
    public function getQualifiedName()
    {
        return implode('\\', $this->name);
    }

    /**
     * @return string the namespace name, omitting the object.
     */
    public function getNamespace()
    {
        return implode('\\', array_slice($this->name, 0, count($this->name) - 1));
    }
}
