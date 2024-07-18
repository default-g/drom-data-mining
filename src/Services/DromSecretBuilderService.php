<?php

namespace Default\DromDataMining\Services;

use Default\DromDataMining\Http\HttpParam;
use Default\DromDataMining\Interfaces\SecretBuilderInterface;

/**
 * Данный класс построен на основе декомпилированного кода приложения
 * DromSecretBuilder.java
 */
class DromSecretBuilderService implements SecretBuilderInterface
{
    /**
     * @param HttpParam[] $httpParams
     * @return string
     */
    public function build(array $httpParams): string
    {
        return '';
    }


    /**
     * @param HttpParam[] $parameters
     * @param int $i12
     * @return string
     */
    protected function buildFromParams(array $parameters, int $i12): string
    {
        $map = [];
        foreach ($parameters as $parameter) {
            $value = $parameter->getValues()[$i12];
            $list2 = $map[$parameter->getKey()] ?? null;
            if ($list2 === null) {
                $list2 = [];
                $map[$parameter->getKey()] = $list2;
            }
            $list2[] = $parameter;
        }

        $string = '';
        foreach ($map as $list3) {
            if ($this->d($list3, $i12 + 1)) {
                $string .= $this->buildFromParams($list3, $i12 + 1);
            } else {
//                $string .= $list3[0]->getValues()[$i12];
            }
        }

        return '';
    }


    protected function d(array $list, int $i12): bool
    {
        if (count($list) <= 1) {
            return false;
        }

        $iterator = new \ArrayIterator($list);

        foreach ($list as $a12) {
            $i13 = $i12 + 1;
            if (count($a12) !== $i13) {
                $aVar = $a12[$i13];
                return true;
            }
        }

        return false;
    }


    protected function c(array $list): string
    {
        if (count($list) === 0) {
            return '';
        }
        $string = '';
        foreach ($list as $item) {

        }

    }

}
