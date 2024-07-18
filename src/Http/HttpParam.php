<?php

namespace Default\DromDataMining\Http;

class HttpParam
{
    public function __construct(private string $key, private array $values)
    {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValues(): array
    {
        return $this->values;
    }

}
