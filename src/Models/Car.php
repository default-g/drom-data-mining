<?php

namespace Default\DromDataMining\Models;

class Car
{
    public function __construct(
        public ?int $id = null,
        public ?string $url = null,
        public ?string $model = null,
        public ?string $brand = null,
        public ?float $price = null,
        public ?string $priceRating = null,
        public ?string $generation = null,
        public ?string $complectation = null,
        public ?int $mileage = null,
        public ?bool $withoutRussianMileage = null,
        public ?string $color = null,
        public ?string $bodyType = null,
        public ?int $enginePower = null,
        public ?string $fuelType = null,
        public ?float $engineVolume = null,
        public array $imageLinks = []
    )
    {
    }


    public function __toString(): string
    {
        return json_encode($this);
    }

}
