<?php

namespace app\service\log\monolog\Handler;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class MonologFileStreamHandler extends StreamHandler
{
    protected $logFilePath;
    protected $appName;
    protected $logFileName;
    protected $logFileStream;
    protected $channel;
    protected $level;
    public function __construct(array $config){
        $this->channel = $config['channel'];
        $this->level = $config['level'];
        $this->setAppName($config['appName']);
        $this->setLogFilePath($config['path']);
        $this->setLogFileName();
        $this->setLogFileStream();
        parent::__construct($this->logFileStream,Logger::DEBUG,true,0777);
    }
    public function setLogFilePath(string $logFilePath):void{
        $this->logFilePath = $logFilePath. '/' .$this->appName."/". date('Ymd').'/';;
    }
    public function setAppName(string $appName):void {
        $this->appName = $appName;
    }
    protected function setLogFileName():void {
        $this->logFileName = $this->channel.($this->level?"_".strtolower(Logger::getLevelName($this->level)):"").".log";
    }
    protected function setLogFileStream():void {
        $this->logFileStream = $this->logFilePath.$this->logFileName;
    }
}
