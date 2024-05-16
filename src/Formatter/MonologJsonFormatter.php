<?php
namespace MLog\Formatter;
use Monolog\Formatter\JsonFormatter;

class MonologJsonFormatter extends JsonFormatter
{
    public function __construct(array $config=[])
    {
        parent::__construct();
    }
}