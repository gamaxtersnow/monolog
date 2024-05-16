<?php

namespace app\service\log\monolog\Formatter;
use Monolog\Formatter\LineFormatter;

class MonologLineFormatter extends LineFormatter {

    public function __construct(array $config=[])
    {
        parent::__construct($this->getLineFormat());
    }

    public function getLineFormat():string {
        return '{"time":"%datetime%","channel":"%channel%","level":"%level_name%","message":"%message%","context":%context%,"timestamp":%timestamp%,"microseconds":%microseconds%,"extra":%extra%}'."\n";
    }
}