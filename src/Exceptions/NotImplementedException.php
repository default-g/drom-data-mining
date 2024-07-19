<?php

namespace Default\DromDataMining\Exceptions;

class NotImplementedException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Not implemented');
    }
}
