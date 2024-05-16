<?php
namespace app\service\log\monolog\Formatter;
use Monolog\Formatter\HtmlFormatter;

class MonologHtmlFormatter extends HtmlFormatter
{
    public function __construct(array $config){
        parent::__construct(null);
    }
}
