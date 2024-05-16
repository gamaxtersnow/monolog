<?php
    namespace app\service\log\monolog\Formatter;

    use Monolog\Formatter\ElasticaFormatter;
    use Monolog\Logger;

    class MonologElasticaFormatter extends ElasticaFormatter {
        public function __construct(array $config=[])
        {
            $index = $config['appName'];
            $type = $config['channel'].($config['level']?"_".strtolower(Logger::getLevelName($config['level'])):"");
            parent::__construct($index, $type);
        }
    }