<?php
namespace app\service\log\monolog\Formatter;
use Monolog\Formatter\LogstashFormatter;
use Monolog\Logger;

class MonologLogstashFormatter extends LogstashFormatter{
    public function __construct(array $config)
    {
        $applicationName = $config['appName'];
        $systemName = $config['channel'].($config['level']?"_".strtolower(Logger::getLevelName($config['level'])):"");
        parent::__construct($applicationName, $systemName);
    }
}