<?php


require __DIR__ . '/vendor/autoload.php';


use Default\DromDataMining\Exceptions\FailToOpenFileException;
use Default\DromDataMining\Models\Car;
use Default\DromDataMining\Services\DromApiParserService;
use Default\DromDataMining\Services\DromWebParserService;
use GuzzleHttp\Client;

const CSV_FILENAME = 'Data.csv';
const CSV_COLUMNS = [
    'Номер объявления',
    'URL',
    'Марка',
    'Модель',
    'Цена',
    'Отметка цены',
    'Поколение',
    'Комплектация',
    'Пробег',
    'Без пробега по РФ',
    'Цвет',
    'Тип кузова',
    'Мощность двигателя',
    'Тип топлива',
    'Объем двигателя',
];

$file = fopen(CSV_FILENAME, 'w');

if (!$file) {
    throw new FailToOpenFileException();
}

$client = new Client();

$dromApiParserService = new DromWebParserService($client);

fputcsv($file, CSV_COLUMNS);

foreach ($dromApiParserService->parse() as $car) {

    echo $car . PHP_EOL;
    fputcsv($file, [
        $car->id,
        $car->url,
        $car->brand,
        $car->model,
        $car->price,
        $car->priceRating,
        $car->generation,
        $car->complectation,
        $car->mileage,
        $car->withoutRussianMileage ? 'Да' : 'Нет',
        $car->color,
        $car->bodyType,
        $car->enginePower,
        $car->fuelType,
        $car->engineVolume
    ]);
}
