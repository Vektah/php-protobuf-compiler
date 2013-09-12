<?php

namespace vektah\protobuf\compiler;

use vektah\parser_combinator\exception\ParseException;

/**
 * Resolves types within a compile.
 */
class Resolver
{
    private $definitions = [];

    private $baseNamespace;

    /**
     * @param array $baseNamespace array separated namespace to treat as the root of all messages.
     */
    public function __construct(array $baseNamespace = [])
    {
        $this->baseNamespace = $baseNamespace;
    }

    /**
     * @param array $namespace array of namespace parts relative to the base namespace.
     * @param string $name the object name
     *
     * @return array namespace and class
     */
    public function getFQN(array $namespace, $name) {
        if ($name[0] == '.') {
            $nameParts = explode('.', $name);
            $namespace = array_merge($this->baseNamespace, $nameParts);
        } else {
            // This is necessary because names can be '.' separated namespaces in themselves.
            $nameParts = explode('.', $name);
            $namespace = array_merge($this->baseNamespace, $namespace, $nameParts);
        }

        $name = array_pop($namespace);

        // Tolower the namespace section
        array_walk($namespace, function(&$data) {
            $data = strtolower($data);
        });

        return array_merge($namespace, [$name]);
    }

    public function define(array $namespace, $name, $element)
    {
        $definition = new Definition($this->getFQN($namespace, $name), $element);

        $this->definitions[$definition->getQualifiedName()] = $definition;
    }

    /**
     * @param array $namespace
     * @param string $name
     *
     * @return Definition
     */
    public function fetch(array $namespace, $name)
    {
        if ($name[0] == '.') {
            $lookup = new Definition($this->getFQN($namespace, $name));

            if (isset($this->definitions[$lookup->getQualifiedName()])) {
                return $this->definitions[$lookup->getQualifiedName()];
            }
        } else {
            $fqn = $this->getFQN($namespace, $name);
            $name = array_pop($fqn);

            for ($depth = count($fqn); $depth >= 0; $depth--) {
                $lookup = new Definition(array_merge(array_slice($fqn, 0, $depth), [$name]));
                if (isset($this->definitions[$lookup->getQualifiedName()])) {
                    return $this->definitions[$lookup->getQualifiedName()];
                }
            }
        }

        throw new ParseException("Unable to resolve {$name} within '" . implode('.', $namespace) . "' or its parents. Defined symbols are: \n" . implode("\n", array_keys($this->definitions)));
    }
}
