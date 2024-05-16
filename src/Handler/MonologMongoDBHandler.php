<?php

namespace MLog\Handler;

use Monolog\Handler\MongoDBHandler;
use Monolog\Logger;

class MonologMongoDBHandler extends MongoDBHandler{
    public function __construct(array $config)
    {
        $host = $config['host'];
        $database = $config['appName'];
        $collection = $config['channel'].($config['level']?"_".strtolower(Logger::getLevelName($config['level'])):"");
        $mongo = null;
        parent::__construct($mongo, $database, $collection);
    }
}
