<?php

require __DIR__ . '/vendor/autoload.php';

use Default\DromDataMining\Exceptions\FailToOpenFileException;
use Default\DromDataMining\Http\HttpClientFactory;
use Default\DromDataMining\Services\DromWebParserService;

const ARCHIVE_NAME = 'Result_Crown.zip';
const SAVE_FOLDER = __DIR__ . DIRECTORY_SEPARATOR . 'data';
const DEBUG = false;
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


if (!is_dir(SAVE_FOLDER)) {
    mkdir(SAVE_FOLDER);
}

$file = fopen(SAVE_FOLDER . DIRECTORY_SEPARATOR . CSV_FILENAME, 'w');

if (!$file) {
    throw new FailToOpenFileException();
}

$client = HttpClientFactory::create();
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

    $directoryForImagesPath = SAVE_FOLDER . DIRECTORY_SEPARATOR . $car->id;

    if (!is_dir($directoryForImagesPath)) {
        mkdir($directoryForImagesPath);
    }

    foreach ($car->imageLinks as $imageLink) {
        $imageFileName = $directoryForImagesPath . DIRECTORY_SEPARATOR . uniqid() . '.jpg';
        file_put_contents($imageFileName, file_get_contents($imageLink));
        sleep(1);
    }
}

fclose($file);

$zip = new ZipArchive();
$zip->open(ARCHIVE_NAME, ZipArchive::CREATE | ZipArchive::OVERWRITE);

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(SAVE_FOLDER),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen(SAVE_FOLDER) + 1);

        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();

shell_exec('rm -rf ' . SAVE_FOLDER);
