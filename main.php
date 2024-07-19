<?php


require __DIR__ . '/vendor/autoload.php';


use Default\DromDataMining\Models\Car;
use Default\DromDataMining\Services\DromApiParserService;
use Default\DromDataMining\Services\DromWebParserService;
use GuzzleHttp\Client;


$client = new Client();

$dromApiParserService = new DromWebParserService($client);
/**
 * @var Car $car
 */
foreach ($dromApiParserService->parse() as $car) {
    echo $car . "\n";
}
