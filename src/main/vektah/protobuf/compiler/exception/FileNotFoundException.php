<?php

namespace vektah\protobuf\compiler\exception;

use Exception;

class FileNotFoundException extends Exception
{
    public function __construct($filename)
    {
        parent::__construct("A required file was not found '$filename'");
    }
}
