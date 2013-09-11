<?php

namespace vektah\protobuf\compiler\cli;

use Symfony\Component\Console\Application as SymfonyApplication;
use vektah\protobuf\compiler\Compiler;

class Application extends SymfonyApplication {
    public function __construct()
    {
        parent::__construct();
        $this->setName('PHP Protocol Buffer Compiler');
        $this->setVersion(Compiler::VERSION);

        $this->add(new Compile());
    }
}
