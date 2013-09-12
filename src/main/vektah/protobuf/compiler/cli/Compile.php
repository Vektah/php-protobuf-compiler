<?php

namespace vektah\protobuf\compiler\cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vektah\protobuf\compiler\Compiler;

class Compile extends Command
{
    protected function configure()
    {
        $this->setName('compile');
        $this->setDescription('Generate .php source files from the .proto files');

        $this->addArgument('filename', InputArgument::REQUIRED, "The file to compile");

        $this->addOption('out', null, InputOption::VALUE_OPTIONAL, "The directory to generate files in", '.');
        $this->addOption('input_dir', 'I', InputOption::VALUE_OPTIONAL, 'The directory to ready .proto files from', '.');
        $this->addOption('namespace', 'N', InputOption::VALUE_OPTIONAL, 'The base namespace for all compiled files, separated by \\', '');
        $this->addOption('template_dir', 'T', InputOption::VALUE_OPTIONAL, 'The name of the directory containing the source templates.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('filename');
        $inputDir = $this->checkPath($input->getOption('input_dir'));
        $outputDir = $this->checkPath($input->getOption('out'));
        $namespace = explode('\\', $input->getOption('namespace'));
        $templateDir = $input->getOption('template_dir');

        if ($namespace[0] == '') {
            $namespace = [];
        }

        $compiler = new Compiler($inputDir, $outputDir, $namespace, $templateDir);

        $compiler->compile($file);
    }

    private function checkPath($path)
    {
        if (!file_exists($path)) {
            mkdir($path);
        } elseif (!is_dir($path)) {
            throw new \InvalidArgumentException($path . ' was found but is not a directory.');
        }

        return realpath($path);
    }
}
