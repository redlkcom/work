<?php
namespace app\api\command;
error_reporting(E_ALL ^ E_NOTICE);
////执行shell  sh  import.sh  >t`date +%Y-%m-%d+%H`.log 
/*
gift_cache  后台任务执行 处理礼物到账到主播
*/
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Cache;
use think\Config;
use app\common\logic\UserLogic;
class Commission extends Command
{
    protected function configure()
    {
       /// Config::load(APP_PATH . 'api/config.php'); 
    
        $this->setName('Commission')->setDescription('Here is the remark ');
        
    }
 
    protected function execute(Input $input, Output $output)
    {	ini_set("magic_quotes_runtime", 0);set_time_limit (0);ini_set('max_execution_time', '0');ini_set('memory_limit','228M');

		$str= $remain=UserLogic::Commission( ); 
          
        
  //       print_r($str);
        
        $output->writeln("Commission:"); 
    }
}

?>