<?php

namespace vektah\protobuf\compiler;

use vektah\parser_combinator\language\proto\Enum;
use vektah\parser_combinator\language\proto\Extend;
use vektah\parser_combinator\language\proto\Identifier;
use vektah\parser_combinator\language\proto\Import;
use vektah\parser_combinator\language\proto\Message;
use vektah\parser_combinator\language\proto\Service;
use vektah\parser_combinator\language\ProtoParser;
use vektah\protobuf\compiler\exception\FileNotFoundException;

class Compiler
{
    const VERSION = '0.1.0';
    private $parser;
    /**
     * @var \Twig_Template[]
     */
    private $templates = [];

    private $inputDir;
    private $outputDir;
    private $resolver;

    private static $phpTypeMap = [
        'double' => 'float',
        'float' => 'float',
        'int32' => 'int',
        'int64' => 'int',
        'uint32' => 'int',
        'uint64' => 'int',
        'sint32' => 'int',
        'sint64' => 'int',
        'fixed32' => 'int',
        'fixed64' => 'int',
        'sfixed32' => 'int',
        'sfixed64' => 'int',
        'bool' => 'bool',
        'string' => 'string',
        'bytes' => 'string',
        'enum' => 'int',
    ];

    public function __construct($inputDir, $outputDir, array $namespace, $template_dir = null)
    {
        $this->inputDir = $inputDir;
        $this->outputDir = $outputDir;
        $this->resolver = new Resolver($namespace);

        $template_dir = $template_dir ? $template_dir : realpath(__DIR__ . '/../../../../resources/templates/psr2');
        $twig_loader = new \Twig_Loader_Filesystem($template_dir);
        $twig = new \Twig_Environment($twig_loader);

        $this->templates = [
            'enum' => $twig->loadTemplate('enum.twig'),
            'message' => $twig->loadTemplate('message.twig')
        ];
        $this->parser = new ProtoParser();
    }

    private function parse($filename) {
        if (!file_exists($filename)) {
            throw new FileNotFoundException($filename);
        }

        return $this->parser->parse(file_get_contents($filename));
    }

    private function import($basedir, $filename) {
        $initialDir = getcwd();
        chdir($basedir);
        $importTree = $this->parse($filename);

        chdir($initialDir);

        $importTree->traverse(function($element, $namespace) use ($filename) {
            if ($element instanceof Import) {
                $this->import(dirname($filename), $element->name);
            }

            if ($element instanceof Enum || $element instanceof Message || $element instanceof Service) {
                $this->resolver->define($namespace, $element->name, $element);
            }
        });
    }


    public function compile($sourceFilename)
    {
        $parse_tree = $this->parse($sourceFilename);

        // Scan over everything and collect definitions.
        $parse_tree->traverse(function($element, $namespace) use ($sourceFilename) {
            if ($element instanceof Import) {
                $this->import(dirname($sourceFilename), $element->name);
            }

            if ($element instanceof Enum || $element instanceof Message || $element instanceof Service) {
                $this->resolver->define($namespace, $element->name, $element);
            }
        });

        // In the second pass we can resolve references (eg enums in other files)
        $parse_tree->traverse(function($element, $namespace) use ($sourceFilename) {
            if ($element instanceof Enum || $element instanceof Message) {
                $this->export($namespace, $element, $sourceFilename);
            }
        });
    }

    private function export(array $namespace, $element, $sourceFilename) {
        $elementNamespace = $this->resolver->fetch($namespace, $element->name);

        $outputFilename = $this->outputDir . '/' . str_replace('\\', '/', $elementNamespace->getQualifiedName()) . '.php';

        if ($element instanceof Enum) {
            $source = $this->templates['enum']->render(['enum' => $element, 'namespace' => $elementNamespace->getNamespace(), 'sourceFilename' => $sourceFilename]);
        } elseif ($element instanceof Message) {
            // If codegen is disabled just return.
            foreach ($element->options as $option) {
                if ($option->identifier === 'codegen' && !$option->value) {
                    return;
                }
            }

            $uses = [];
            $fieldNamespace = array_merge($namespace, [strtolower($element->name)]);

            $base = null;
            if (count($element->members) == 1 && $element->members[0] instanceof Extend) {
                $extends = $element->members[0];

                $name = $element->name;
                $base = $this->resolver->fetch($namespace, $extends->name)->definition;

                $element = new Message($name, array_merge($extends->members));
            }

            $seen_indexes = [];

            foreach ($element->fields as $field) {
                if (isset($seen_indexes[$field->index])) {
                    throw new \RuntimeException("Duplicate index {$field->index} in $sourceFilename");
                }
                $seen_indexes[$field->index] = true;
                if ($field->default instanceof Identifier) {
                    $definition = $this->resolver->fetch($fieldNamespace, $field->type);

                    $uses[] = $definition->getQualifiedName();

                    $field->default = "{$definition->getShortName()}::{$field->default->name}";
                } elseif (is_string($field->default)) {
                    $field->default = "'{$field->default}";
                }

                if (isset(self::$phpTypeMap[$field->type])) {
                    $field->phpType = self::$phpTypeMap[$field->type];
                    $field->protoType = 'Type::' . strtoupper($field->type);
                } else {
                    // User types!
                    $type = $this->resolver->fetch($fieldNamespace, $field->type);
                    if ($type->definition instanceof Enum) {
                        $field->phpType = self::$phpTypeMap['enum'];
                        $field->protoType = 'Type::INT32';
                    } else {
                        $uses[] = $type->getQualifiedName();
                        $field->phpType = $type->getShortName();
                        $field->protoType = $type->getShortName() . '::class';
                        $field->typeHint = $type->getShortName() . ' ';
                    }
                }

                if ($field->label == 'required') {
                    $field->required = 'true';
                } else {
                    $field->required = 'false';
                }

                if ($field->label == 'repeated') {
                    $field->repeated = 'true';
                    $field->typeHint = 'array ';
                    $field->phpType = $field->phpType . '[]';
                }
            }

            $uses = array_unique($uses);

            $template = $this->templates['message'];

            $source = $template->render([
                'message' => $element,
                'namespace' => $elementNamespace->getNamespace(),
                'sourceFilename' => $sourceFilename,
                'base' => $base,
                'uses' => $uses
            ]);
        }

        if (!is_dir(dirname($outputFilename))) {
            mkdir(dirname($outputFilename), 0775, true);
        }

        file_put_contents($outputFilename, $source);
    }
}
