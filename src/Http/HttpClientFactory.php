<?php

namespace Default\DromDataMining\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;

class HttpClientFactory
{
    public static function create(): Client
    {
        $options = [];
        if (DEBUG) {
            $options['debug'] = true;
        }

        $stack = HandlerStack::create();
        $stack->push(GuzzleRetryMiddleware::factory());

        $options['stack'] = $stack;

        return new Client($options);
    }

}
