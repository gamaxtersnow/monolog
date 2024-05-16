<?php

namespace app\service\log\monolog;

use app\service\log\monolog\Formatter\MonologElasticaFormatter;
use app\service\log\monolog\Formatter\MonologHtmlFormatter;
use app\service\log\monolog\Formatter\MonologJsonFormatter;
use app\service\log\monolog\Formatter\MonologLineFormatter;
use app\service\log\monolog\Formatter\MonologLogstashFormatter;
use app\service\log\monolog\Handler\MonologElasticSearchHandler;
use app\service\log\monolog\Handler\MonologFileStreamHandler;
use app\service\log\monolog\Handler\MonologMongoDBHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;
use think\log\driver\File;


class MonologLogger extends File implements LoggerInterface
{
    private $_config;
    private $_appName;
    private $_channel;
    private $_formatter;
    private $_logger;
    private $_logConfig;
    private const _DEFAULT_CHANNEL_NAME = 'app';
    private const _DEFAULT_HANDLER = 'file';
    private static $loggerInstance = [];
    private  $streamHandler = [
        'file'=>MonologFileStreamHandler::class,
        'mongo'=>MonologMongoDBHandler::class,
        'es'=>MonologElasticSearchHandler::class
        ];
    private const _DEFAULT_FORMATTER = 'line';
    private $formatter = [
        'line'=>MonologLineFormatter::class,
        'html'=>MonologHtmlFormatter::class,
        'json'=>MonologJsonFormatter::class,
        'logstash'=>MonologLogstashFormatter::class,
        'es'=>MonologElasticaFormatter::class
    ];
    public function __construct(){
        $this->_logConfig = config('monolog');
    }
    private function _setFormatter(){
        $this->_formatter = invoke($this->_getFormatter($this->_config['formatter']),[$this->_config]);
    }
    private function _setCustomerHandler():void
    {
        $this->_setFormatter();
        $customerHandler = invoke($this->_getHandler($this->_config['type']),[$this->_config])->setFormatter($this->_formatter);
        $this->_logger->pushHandler($customerHandler);
    }
    private function _getChannelConfig(string $channel=''):self{
        //获取项目名称
        $this->_appName = app('http')->getName();
        //获取monolog日志配置
        //获取所有项目配置
        $apps = $this->_logConfig['apps'] ?? [];
        if (empty($apps)) {//无任何app配置
            //继承默认配置
            $this->_setDefaultConfig();
            return $this;
        }
        //获取app配置
        $appConfig = $apps[$this->_appName] ?? [];
        if(empty($appConfig)){
            //继承默认配置
            $this->_setDefaultConfig();
            return $this;
        }
        $channelConfig = $appConfig[$channel]??[];
        if(empty($channelConfig)){
            //获取app默认配置
            $appDefaultConfig = $appConfig['default']??[];
            if(empty($appDefaultConfig)){
                //继承默认配置
                $this->_setDefaultConfig($channel);
                return $this;
            }
            //继承app默认配置
            $this->_config = $appDefaultConfig;
            $this->_channel = $channel?:self::_DEFAULT_CHANNEL_NAME;
            return $this;
        }
        //获取channel配置
        $this->_config = $channelConfig;
        $this->_channel = $channel;
        return $this;
    }
    private function _setDefaultConfig(string $channel=''):void{
        $this->_config = $this->_logConfig['default'];
        $this->_channel = $channel?:self::_DEFAULT_CHANNEL_NAME;
    }
    private function _getFormatter(string $formatter = self::_DEFAULT_FORMATTER): string{
        return $this->formatter[$formatter] ?? $this->formatter[self::_DEFAULT_FORMATTER];
    }
    private function _getHandler(string $handler = self::_DEFAULT_HANDLER): string{
        return $this->streamHandler[$handler] ?? $this->streamHandler[self::_DEFAULT_HANDLER];
    }
    private function _getLoggerInstanceName(int $level):array {
        $levelName = strtolower(Logger::getLevelName($level));
         $instance = [
                    'appName'=>$this->_appName,
                    'channel'=>$this->_channel,
                    //判定配置中是否配置了该错误级别单独记录
                    'level'=>in_array($levelName,$this->_config['apart_level']??[])?$level:0,
            ];
         $this->_config = array_merge($this->_config,$instance);
         return $instance;
    }
    private function _getChannelName(int $level):string {

        $levelName = strtolower(Logger::getLevelName($level));
        $levelName = in_array($levelName,$this->_config['apart_level']??[])?'_'.$levelName:'';
        return $this->_appName."_".$this->_channel.$levelName;
    }
    private function _getInstance(int $level):Logger {
        $instanceName = implode('_',$this->_getLoggerInstanceName($level));
        if(array_key_exists($instanceName,self::$loggerInstance)){
            return self::$loggerInstance[$instanceName];
        }
        $this->_logger = new Logger($this->_getChannelName($level));
        $this->_setFormatter();
        $this->_setCustomerHandler();
        $this->_logger->pushProcessor(new UidProcessor(32));
        $this->_logger->pushProcessor(new WebProcessor());
        $this->_logger->pushProcessor(function($record) {
            $record['timestamp'] = time();
            $record['microseconds'] =microtime(true);
            return $record;
        });
        $this->_logger->setTimezone(new \DateTimeZone('PRC'));
        return self::$loggerInstance[$instanceName] = $this->_logger;
    }
    public function channel(string $channel=''):self
    {
        return $this->_getChannelConfig($channel);
    }

    public function emergency($message, array $context = array())
    {
        $this->_getInstance(Logger::EMERGENCY)->emergency($message,  $context);
    }
    public function alert($message, array $context = array())
    {
        $this->_getInstance(Logger::ALERT)->alert($message,  $context);
    }
    public function critical($message, array $context = array())
    {
        $this->_getInstance(Logger::CRITICAL)->critical($message,  $context);
    }
    public function error($message, array $context = array())
    {
        $this->_getInstance(Logger::ERROR)->error($message,  $context);
    }
    public function warning($message, array $context = array())
    {
        $this->_getInstance(Logger::WARNING)->warning($message,  $context);
    }

    public function notice($message, array $context = array())
    {
        $this->_getInstance(Logger::NOTICE)->notice($message,  $context);
    }
    public function info($message, array $context = array())
    {
        $this->_getInstance(Logger::INFO)->info($message,  $context);
    }
    public function debug($message, array $context = array())
    {
        $this->_getInstance(Logger::DEBUG)->debug($message,  $context);
    }
    public function log($level, $message, array $context = array())
    {
        $this->_getInstance(Logger::toMonologLevel($level))->log($level,$message,  $context);
    }
}