<?php

namespace MLog\Handler;

use Monolog\Handler\ElasticSearchHandler;


class MonologElasticSearchHandler extends ElasticSearchHandler {

    public function __construct(array $config)
    {
        parent::__construct(null,[]);
    }
}

