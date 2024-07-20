<?php

namespace Default\DromDataMining\Exceptions;

class FailToOpenFileException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Failed to open file');
    }
}
