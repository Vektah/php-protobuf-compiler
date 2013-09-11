<?php

namespace vektah\protobuf\compiler;

use vektah\parser_combinator\language\proto\Enum;
use vektah\parser_combinator\language\proto\Identifier;
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

    private static $allegroTypeMap = [
        'double' => 'ProtobufMessage::PB_TYPE_DOUBLE',
        'float' => 'ProtobufMessage::PB_TYPE_FLOAT',
        'int32' => 'ProtobufMessage::PB_TYPE_INT',
        'int64' => 'ProtobufMessage::PB_TYPE_INT',
        'uint32' => 'ProtobufMessage::PB_TYPE_INT',
        'uint64' => 'ProtobufMessage::PB_TYPE_INT',
        'sint32' => 'ProtobufMessage::PB_TYPE_SIGNED_INT',
        'sint64' => 'ProtobufMessage::PB_TYPE_SIGNED_INT',
        'fixed32' => 'ProtobufMessage::PB_TYPE_FIXED32',
        'fixed64' => 'ProtobufMessage::PB_TYPE_FIXED64',
        'sfixed32' => 'ProtobufMessage::PB_TYPE_FIXED32',
        'sfixed64' => 'ProtobufMessage::PB_TYPE_FIXED64',
        'bool' => 'ProtobufMessage::PB_TYPE_BOOL',
        'string' => 'ProtobufMessage::PB_TYPE_STRING',
        'bytes' => 'ProtobufMessage::PB_TYPE_STRING',
        'enum' => 'ProtobufMessage::PB_TYPE_INT'
    ];

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

        $template_dir = $template_dir ? $template_dir : realpath(__DIR__ . '/../../../../resources/templates/allegro-psr2');
        $twig_loader = new \Twig_Loader_Filesystem($template_dir);
        $twig = new \Twig_Environment($twig_loader);

        $this->templates = [
            'enum' => $twig->loadTemplate('enum.twig'),
            'message' => $twig->loadTemplate('message.twig')
        ];
        $this->parser = new ProtoParser();
    }

    public function compile($sourceFilename)
    {
        if (!file_exists($sourceFilename)) {
            throw new FileNotFoundException($sourceFilename);
        }

        $parse_tree = $this->parser->parse(file_get_contents($sourceFilename));

        // Scan over everything and collect definitions.
        $parse_tree->traverse(function($element, $namespace) {
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
            $uses = [];
            $fieldNamespace = array_merge($namespace, [strtolower($element->name)]);

            foreach ($element->fields as $field) {
                if ($field->default instanceof Identifier) {
                    $definition = $this->resolver->fetch($fieldNamespace, $field->type);

                    $uses[] = $definition->getQualifiedName();

                    $field->default = "{$definition->getShortName()}::{$field->default->name}";
                } elseif (is_string($field->default)) {
                    $field->default = "'{$field->default}";
                }

                if (isset(self::$phpTypeMap[$field->type])) {
                    $field->phpType = self::$phpTypeMap[$field->type];
                    $field->allegroType = self::$allegroTypeMap[$field->type];
                } else {
                    // User types!
                    $type = $this->resolver->fetch($fieldNamespace, $field->type);
                    if ($type->definition instanceof Enum) {
                        $field->phpType = self::$phpTypeMap['enum'];
                        $field->allegroType = self::$allegroTypeMap['enum'];
                    } else {
                        $uses[] = $type->getQualifiedName();
                        $field->phpType = $type->getShortName();
                        $field->allegroType = $type->getShortName() . '::_CLASS';
                    }
                }

                if ($field->label == 'required') {
                    $field->required = 'true';
                } else {
                    $field->required = 'false';
                }
            }

            $uses = array_unique($uses);

            $source = $this->templates['message']->render(['message' => $element, 'namespace' => $elementNamespace->getNamespace(), 'sourceFilename' => $sourceFilename, 'uses' => $uses]);
        }

        if (!is_dir(dirname($outputFilename))) {
            mkdir(dirname($outputFilename), 0775, true);
        }

        echo $outputFilename . "\n";
        file_put_contents($outputFilename, $source);
    }
}
