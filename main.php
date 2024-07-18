<?php


require __DIR__ . '/vendor/autoload.php';


use Default\DromDataMining\Services\DromApiParserService;
use Default\DromDataMining\Services\DromWebParserService;
use GuzzleHttp\Client;


$client = new Client();

$dromApiParserService = new DromWebParserService($client);
$dromApiParserService->parse();
