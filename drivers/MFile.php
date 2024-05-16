<?php

namespace app\service\log\drivers;

use app\service\log\monolog\Formatter\MonologLineFormatter;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use think\App;
use think\log\driver\File;


class MFile extends File
{
    protected $logger;
    protected $apartLevel;
    protected $destination;
    protected $path;
    protected $lineFormatter;
    public function __construct(App $app, $config = [])
    {
        parent::__construct($app, $config);
        $this->apartLevel = $this->config['apart_level']??[];
        $this->destination = $this->getMasterLogFile();
        $this->path = dirname($this->destination);
        $this->lineFormatter = new MonologLineFormatter();
        $this->logger = new Logger(app('http')->getName());
        $this->logger->pushProcessor(new UidProcessor(32));
        $this->logger->pushProcessor(new WebProcessor());
        $this->logger->pushProcessor(function($record) {
            $record['timestamp'] = time();
            $record['microseconds'] =microtime(true);
            return $record;
        });
        $this->logger->setTimezone(new \DateTimeZone('PRC'));
    }

    public function save(array $log): bool
    {
        try {
            $time = \DateTime::createFromFormat('0.u00 U', microtime())->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format($this->config['time_format']);
            $message = [];
            $info = [];
            foreach ($log as $type => $val) {
                $typeMsg = [];
                foreach ($val as $msg) {
                    if (!is_string($msg)) {
                        $msg = var_export($msg, true);
                    }
                    $typeMsg[] = $this->config['json'] ?
                        json_encode(['msg' => $msg], $this->config['json_options']) :
                        sprintf($this->config['format'] , $msg);
                }
                if (in_array($type, $this->apartLevel)) {
                    $message[$type] = $typeMsg;
                    continue;
                }
                $info[$type] = $typeMsg;
            }
            if($message){
                foreach($message as $type=>$messageList){
                    $filename = $this->getApartLevelFile($this->path, $type);
                    $this->checkLogSize($filename);
                    $this->logger->pushHandler($this->getStreamHandler($filename));
                    foreach ($messageList as $msg) {
                        $this->writeLog($type,$msg);
                    }
                    $this->logger->popHandler();
                }
            }
            if ($info) {
                $this->checkLogSize($this->destination);
                $this->logger->pushHandler($this->getStreamHandler($this->destination));
                foreach ($info as $type => $message) {
                    foreach ($message as $msg){
                        $this->writeLog($type,$msg);
                    }
                }
                $this->logger->popHandler();
            }
            return true;
        }catch (Exception $e){
            echo $e->getMessage();
            return false;
        }
    }
    public function sql($message,$content) {
        call_user_func([$this->logger, 'info'], [$message,$content]);
    }
    protected function writeLog(string $type, string $msg) {
        if(!method_exists($this->logger,$type)){
            $type = 'info';
        }
        call_user_func([$this->logger, $type], $msg);
    }
    /**
     * @throws Exception
     */
    protected function getStreamHandler(string $fileName):StreamHandler {
        $streamHandler = new StreamHandler($fileName, Logger::DEBUG);
        $streamHandler->setFormatter($this->lineFormatter);
        return $streamHandler;
    }
}
