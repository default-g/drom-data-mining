<?php


require __DIR__ . '/vendor/autoload.php';


use Default\DromDataMining\Services\DromApiParserService;

$parser = new DromApiParserService();

$parser->parse();
