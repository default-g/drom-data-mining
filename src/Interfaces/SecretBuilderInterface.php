<?php

namespace Default\DromDataMining\Interfaces;

use Default\DromDataMining\Http\HttpParam;

interface SecretBuilderInterface
{
    /**
     * @param HttpParam[] $parameters
     * @return string
     */
    public function build(array $parameters): string;
}
