<?php

namespace Default\DromDataMining\Models;

class Car
{
    public function __construct(
        public ?int $id,
        public ?string $url,
        public ?string $model,
        public ?string $brand,
        public ?float $price,
        public ?string $priceRating,
        public ?string $generation,
        public ?string $complectation,
        public ?int $mileage,
        public ?bool $withoutRussianMileage,
        public ?string $color,
        public ?string $bodyType,
        public ?int $enginePower,
        public ?string $fuelType,
        public ?float $engineVolume,
    )
    {
    }


    public function __toString(): string
    {
        return json_encode($this);
    }

}
