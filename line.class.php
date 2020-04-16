<?php
namespace line;
class worker{


    private static $pidFile = '';


    public static function entry(){
        global $argv;
        self::check($argv);
        
        if(strrpos($argv[0], '/')){
            $enrtyFile  = substr($argv[0], strrpos($argv[0], '/')+1);
        }else{
            $enrtyFile  = $argv[0];
        }
        $pidFileName    = str_replace('/', '_', __DIR__) . '_' . $enrtyFile . '.pid';
        $pidFile        = __DIR__ . '/' . $pidFileName;
        self::$pidFile  = $pidFile;
        
        if($argv[1] == 'start')     self::start();
        if($argv[1] == 'daemon')    self::daemon();
        if($argv[1] == 'status')    self::status();
        if($argv[1] == 'stop')      self::stop();
        if($argv[1] == 'restart')   self::restart();
        return 0;
    }
    
    
    private static function check($argv){
        if(!isset($argv))               die('error, this php file must start in command-line mode!');

        if(!extension_loaded('pcntl'))  die("error, php extension pcntl is not loaded!\r\n\r\n");
        if(!extension_loaded('posix'))  die("error, php extension posix is not loaded!\r\n\r\n");

        if(count($argv) == 1){
            $help   = "error, lose parameter!\r\n";
            $help   .= "start       start php file program as debug mode\r\n";
            $help   .= "daemon      start php file program as daemon mode\r\n";
            $help   .= "status      view php file command-line running status\r\n";
            $help   .= "stop        stop php file program\r\n";
            $help   .= "restart     restart php file program, and new process will run as daemon mode\r\n\r\n";
            $help   .= "current linePHP version: 1.0_20170405\r\n";
            $help   .= "linePHP is a simple framework for php command-line running\r\n\r\n";
            die($help);
        }

        if(count($argv) !== 2)  die("error, number of this php file command parameters is wrong!\r\n\r\n");

        if(!in_array($argv[1], array('start', 'daemon', 'status', 'stop', 'restart')))
                                die("error, this php file command parameter is wrong!\r\n\r\n");
    }
    
    
    private static function start(){
        global $argv;
        $pidFile    = self::$pidFile;
        if(file_exists($pidFile)){
            $master_pid = file_get_contents($pidFile);
            if(posix_kill($master_pid, 0)) exit("error, this file [{$argv[0]}] is already running\r\n\r\n");
        }
        
        $pid        = getmypid();
        file_put_contents($pidFile, $pid);

        echo "file [{$argv[0]}] start in DEBUG mode\r\n";
        echo "--------------------\033[1m\033[47m linePHP \033[0m--------------------\r\n";
        echo "Press Ctrl-C to quit. Start success.\r\n";
        
        self::main();
    }
    
    
    private static function daemon(){
        global $argv;
        $pidFile = self::$pidFile;
        if(file_exists($pidFile)){
            $master_pid = file_get_contents($pidFile);
            if(posix_kill($master_pid, 0)) exit("error, this file [{$argv[0]}] is already running\r\n\r\n");
        }
        
        $pid = pcntl_fork();
        if($pid == -1){
            @unlink($pidFile);
            die("error, fork child pid failed\r\n");
        }elseif($pid > 0){
            file_put_contents($pidFile, $pid);
            exit;
        }elseif($pid == 0){
            self::main();
        }
        
        return 0;
    }
    
    
    private static function status(){
        global $argv;
        $pidFile = self::$pidFile;
        if(!file_exists($pidFile)){
            echo("file [{$argv[0]}] is not running.\r\n");
            echo("Or, there is an error about non-exists pid file [{$pidFile}]\r\n\r\n");
            exit;
        }
        
        $pid = file_get_contents($pidFile);
        if(posix_kill($pid, 0)){
            echo "file [{$argv[0]}] is running, pid [{$pid}]\r\n\r\n";
        }else{
            @unlink($pidFile);
            exit("file [{$argv[0]}] is not running.\r\n\r\n");
        }
    }
    
    
    private static function stop(){
        echo "program is stopping ...\r\n";
        
        $pidFile = self::$pidFile;
        if(!file_exists($pidFile))      exit;
        
        $master_pid = file_get_contents($pidFile);
        @unlink($pidFile);
        if(posix_kill($master_pid, 0))  posix_kill($master_pid, SIGINT);
        exit;
    }
    
    
    private static function restart(){
        global $argv;
        
        $pidFile = self::$pidFile;
        if(file_exists($pidFile)){
            $master_pid = file_get_contents($pidFile);
            @unlink($pidFile);
            if(posix_kill($master_pid, 0)) posix_kill($master_pid, SIGINT);
        }
        
        list(, $phpBin) = explode(' ', exec('whereis php'), 2);
        
        $pid = pcntl_fork();
        if($pid == 0)   exec("$phpBin $argv[0] daemon");
    }
    
    
    private static function main(){
        declare(ticks=1);
        pcntl_signal(SIGINT, function(){
            $pidFile = self::$pidFile;
            @unlink($pidFile);
            exit("\r\n");
        });
        
        while(1){
            sleep(1);
        }
    }
    
    
}



