<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
//think\facade\Config::setYaconf('thinkphp');
// 应用公共文件

use Aliyun\Core\Config;  
use Aliyun\Core\Profile\DefaultProfile;  
use Aliyun\Core\DefaultAcsClient;  
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest; 
 
/**
 * 阿里云短信发送接口
 * @param $mobile 手机号
 * @param $codeId 模板ID
 * @param $code 短信内容
*/
function sendMsg($mobile,$codeId,$code="",$money=""){    
    require_once '../extend/aliyunsms/vendor/autoload.php';  
    Config::load();             //加载区域结点配置   
    $accessKeyId = "LTAImaKuebIRnRlo";
    $accessKeySecret = "V1ntrGz8EUyfnTfNEfnQutPq9cc8pr";
    
    //$signName = (empty(config('alisms_signname'))?'阿里大于测试专用':config('alisms_signname'));  
    $signName = "蜂鸟网络";
    //短信模板ID 
    $templateCode = $codeId;   
    //短信API产品名（短信产品名固定，无需修改）  
    $product = "Dysmsapi";  
    //短信API产品域名（接口地址固定，无需修改）  
    $domain = "dysmsapi.aliyuncs.com";  
    //暂时不支持多Region（目前仅支持cn-hangzhou请勿修改）  
    $region = "cn-hangzhou";     
    // 初始化用户Profile实例  
    $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);  
    // 增加服务结点  
    DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);  
    // 初始化AcsClient用于发起请求  
    $acsClient= new DefaultAcsClient($profile);  
    // 初始化SendSmsRequest实例用于设置发送短信的参数  
    $request = new SendSmsRequest();  
    // 必填，设置雉短信接收号码  
    $request->setPhoneNumbers($mobile);  
    // 必填，设置签名名称  
    $request->setSignName($signName);  
    // 必填，设置模板CODE  
    $request->setTemplateCode($templateCode);  
    // 可选，设置模板参数     
    if($code != "") {
        $request->setTemplateParam(json_encode(array(  // 短信模板中字段的值
            "code" => $code
        ), JSON_UNESCAPED_UNICODE));
    }
    if($money != "") {
        $request->setTemplateParam(json_encode(array(  // 短信模板中字段的值
            "money" => $money
        ), JSON_UNESCAPED_UNICODE));
    }
    //发起访问请求  
    $acsResponse = $acsClient->getAcsResponse($request);   
    //返回请求结果  
    $result = json_decode(json_encode($acsResponse),true); 
    return $result;  
}


/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名
 */
if (!function_exists('sign')) {
    function sign($data)
    {
        // 数据类型检测
        if (!is_array($data)) {
            $data = (array)$data;
        }
        ksort($data); // 排序
        $code = http_build_query($data); // url编码并生成query字符串
        $sign = sha1($code); // 生成签名
        return $sign;
    }
}

/**打印输出
 * @param $arr
 * @author 原点 <467490186@qq.com>
 */
if (!function_exists('p')) {
    function p($arr)
    {
        echo '<pre>' . print_r($arr, true) . '</pre>';
    }
}

/**
 * 将返回的数据集转换成树
 * @param  array   $list  数据集
 * @param  string  $pk    主键
 * @param  string  $pid   父节点名称
 * @param  string  $child 子节点名称
 * @param  integer $root  根节点ID
 * @return array          转换后的树
 */
if (!function_exists('list_to_tree')) {
    function list_to_tree($list, $pk='id', $pid = 'pid', $child = 'child', $root = 0) {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId =  $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }
}

/**
 * 获取当前登陆用户uid
 * @return mixed
 * @author 原点 <467490186@qq.com>
 */
if (!function_exists('get_user_id')) {
    function get_user_id(){
        return session('user_auth.uid');
    }
}
if (!function_exists('get_user_id_sp')) {
    function get_user_id_sp(){
        return session('user_auth_sp.id');
    }
}
function http_curl($url, $data =[], $header=[], $ispost=true){

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //判断是否加header
    if ($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
    }
    //判断是否是POST请求
    if ($ispost) {
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $output = curl_exec($ch);
    curl_close ($ch);
    //打印获得的数据
    return $output;
}

/**
 * @param string $msg 待提示的消息
 * @param string $url 跳转地址
 * @param int $time   弹出维持时间（单位秒）
 * @author 原点 <467490186@qq.com>
 */
function alert_error($msg='', $url=null, $time=3){
    if (is_null($url)) {
        $url = 'parent.location.reload();';
    } else {
        $url = 'parent.location.href=\''.$url.'\'';
    }
    if ( request()->isAjax() ) {
        $str = [
            'code' => 0,
            'msg'  => $msg,
            'url'  => $url,
            'wait' => $time,
        ];
        $response = think\Response::create($str, 'json');
    } else {
        $str = '<script type="text/javascript" src="/layui/layui.js"></script>';
        $str .= '<script>
                    layui.use([\'layer\'],function(){
                       layer.msg("'.$msg.'",{icon:"5",time:'.($time*1000).'},function() {
                         '.$url.'
                       });
                    })
                </script>';
        $response = think\Response::create($str, 'html');
    }
    throw new think\exception\HttpResponseException($response);
}

/**
 * 获取用户编号
 * 规则：3位数月份 + 8位userid，不足位数用0补足
 */
function get_user_code($month,$user_id)
{   //500开头
   //
    return sprintf("%03d",$month) . sprintf("%08d",$user_id);
}

/**
 * 通过tax_class获取个税比例
 */
function get_person_revenue($tax_class)
{
    switch ($tax_class)
    {
        case 101:
            return 0.3;
            break;
        case 201:
            return 0.3;
            break;
        case 202:
            return 1.5;
            break;
        case 301:
            return 1.5;
            break;
        case 401:
            return 0.3;
            break;
        case 501:
            return 0.4;
            break;
        case 601:
            return 0.8;
            break;
        default:
            return 0.3;
    }  



}




/*
读取Excel文件
*/
function  reader_excel($filename){
    $type = strtolower( pathinfo($filename, PATHINFO_EXTENSION) ); 
    $objPHPExcel = new \PHPExcel\PHPExcel(); 
    if( $type=='xlsx'||$type=='xls' ){ 
        if($type=='xls'){
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            
        }else{
            $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        }
        $objReader->setReadDataOnly(true);
        $PHPExcelObj = $objReader->load($filename); 
     }else if( $type=='csv' ){ 
        if (mb_check_encoding(file_get_contents($filename), 'UTF-8'))
        {
            $setInputEncoding='UTF-8' ;
        }
        else
        {
        
             $setInputEncoding='Windows-1255' ;
        }

 
        $objReader = PHPExcel_IOFactory::createReader('CSV')
         ->setDelimiter(',')  
         ->setInputEncoding($setInputEncoding) //不设置将导致中文列内容返回boolean(false)或乱码   
         ->setEnclosure('"')   
         ->setSheetIndex(0); 
        $PHPExcelObj = $objReader->load($filename); 
    }


    // $objReader = PHPExcel_IOFactory::createReader('Excel5');
    // $objReader->setReadDataOnly(true);
    // $PHPExcelObj = $objReader->load($filename); 
    $currentSheet = $PHPExcelObj->getSheet(0);            //选取第一张表单(Sheet1)为当前操作的表单
    $sheets['numRows'] = $currentSheet->getHighestRow(); 
    $sheets['cells']=$currentSheet->toArray( );
    return  $sheets;    
}

/*
*导出 Excel文件
*/
function  export_excel($headers,$datas,$filename='excel'){
    require'../extend/PHPExcel/PHPExcel.php';
       
    $objPHPExcel = new  \PHPExcel(); 

    foreach ($headers as $key => $value) { 
        $pColumn_name=PHPExcel_Cell::stringFromColumnIndex($key);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($pColumn_name.'2', $value);
    } 

    $i = 3;
    if(is_array($datas)){     
        
        foreach($datas as $key=>$col_datas){ 
     
            foreach ($col_datas as $col_k => $col_value) {
                $pColumn_name=PHPExcel_Cell::stringFromColumnIndex($col_k);
                $objPHPExcel->getActiveSheet()->setCellValue($pColumn_name.$i, ' '.$col_value,PHPExcel_Cell_DataType::TYPE_STRING);
                 
            }
             

         $i++;
        }
    }
 

    ob_end_clean();
    $filename=$filename.date('Ymd');
    $xlsTitle = iconv('utf-8', 'gb2312', $filename);//文件名称
    $fileName =$xlsTitle ;//or $xlsTitle 文件名称可根据自己情况设定
    // header('Content-Type: application/vnd.ms-excel;charset=UTF-8;name="'.$xlsTitle.'.xls"');

    // header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
    // header('Cache-Control: max-age=0');
    // // If you're serving to IE 9, then the following may be needed
    // header('Cache-Control: max-age=1');

    // // If you're serving to IE over SSL, then the following may be needed
    // header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    // header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    // header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    // header ('Pragma: public'); // HTTP/1.0
     // $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    // $objWriter->save('php://output');
    // 

    
    header('Content-type:application/vnd.ms-excel;charset=UTF-8;name="'.$xlsTitle.'.xlsx"');
    header("Content-Disposition:attachment;filename=$fileName.xlsx");//attachment新窗口打印inline本窗口打印

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    
     exit;

}

function get_agreefiles($uid,$filename=''){//获得签名协议文件
    $path = UPLOAD_PATH; 
    $agreefiles=config('agreefiles'); 
    $urlp='/upload/useragrees/'.$uid.'/';
    if($filename){
        $file_path=$path.'useragrees/'.$uid.'/'.$filename.'.pdf';//保存文件
        if( is_file($file_path)){
            return ['url'=>$urlp.$filename.'.pdf','filename'=>$agreefiles[$filename]];
        }else{
            return false;
        }
    }else{
        $dir_path=$path.'useragrees/'.$uid.'/';//保存文件
        
        if(is_dir($dir_path)){
            $data = scandir($dir_path);
            foreach ($data as $value){
                if($value != '.' && $value != '..'){
                    $filename=intval($value);
                    $arr[] = ['url'=>$urlp.$value ,'filename'=>$agreefiles[$filename]];     
                }
            }
            return $arr; 
        }else{
            return false;
        }
    }
    
}
//检查 目录
function checkPath($path)
    {
        if (is_dir($path)) {
            return true;
        }

        if (mkdir($path, 0755, true)) {
            return true;
        } else {
             
            return false;
        }
    }



    /**
 * [zip_files 多文件(文件夹)压缩]
 * @Author   Ray
 * @DateTime 2018-01-29
 * @param    [type]     $zipName [压缩文件(文件夹)名称]
 * @param    [type]     $files   [被压缩文件(夹)名，多个用逗号分隔]
 * @return   [boolean]              [true|false]
 */
function zip_files($zip,$files){
    //遍历文件名参数
    foreach($files as $v){ 
        //是文件则加入压缩文件
        if(is_file($v)){
            $zip -> addFile($v);
        }
        //是文件夹则遍历文件夹
        if(is_dir($v)){var_dump($v);
            $handle = opendir($v);
            //注意这里一定要用不全等于false，以防止文件名为'false','0'之类...
            while(($res = readdir($handle))!==false){
                if($res != '.' && $res != '..'){
                    if(is_file($v.'/'.$res)){ 
                        $dpath=substr($v , strripos($v ,'/')+1 );
                        $zip -> addFile($v.'/'.$res,$dpath.'/'.$res);
                    }
                    if(is_dir($v.'/'.$res)){
                        zip_files($zip,$v.'/'.$res);
                    }
                }
            }
        }
    }
}
/*
zipfile  压缩文件名
source  需要压缩的文件夹 数组【】
*/
function  createzip($zipfile,$source){ 
    $zip = new ZipArchive;
    $res=$zip -> open($zipfile,ZipArchive::CREATE|ZipArchive::OVERWRITE);//var_dump($res);
    if($res){
        zip_files($zip,$source);
        $zip -> close();
        return true;
    } 

    return false;
}

function dfopen($url, $limit = 10485760 , $post = '',$ContentType='', $cookie = '', $bysocket = false,$timeout=5,$agent="") {
    if(ini_get('allow_url_fopen') && !$bysocket && !$post) {
        ini_set('user_agent','php');
    $fp = @fopen($url, 'r');
        $s = $t = '';
        if($fp) {
            while ($t=@fread($fp,2048)) {
                $s.=$t;
            }
            fclose($fp);
        }
        if($s) {
            return $s;
        }
    }

    $return = '';
    $agent=$agent?$agent:"Mozilla/5.0 (compatible; Googlebot/2.1; +http:/"."/www.google.com/bot.html)";
    $matches = parse_url($url);
    $host = $matches['host'];
    $script = $matches['path'].(isset($matches['query']) ? '?'.$matches['query'] : '').(isset($matches['fragment']) ? '#'.$matches['fragment'] : '');
    $script = $script ? $script : '/';
    $port = !empty($matches['port']) ? $matches['port'] : 80;
    if($post) {
        $out = "POST $script HTTP/1.1\r\n";
        $out .= "Accept: */"."*\r\n";
        $out .= "Referer: $url\r\n";
        $out .= "Accept-Language: zh-cn\r\n";
        $ContentType=$ContentType?$ContentType:'application/x-www-form-urlencoded';
        $out .= "Content-Type:{$ContentType} \r\n";
        $out .= "Accept-Encoding: none\r\n";
        $out .= "User-Agent: $agent\r\n";
        $out .= "Host: $host\r\n";
        $out .= 'Content-Length: '.strlen($post)."\r\n";
        $out .= "Connection: Close\r\n";
        $out .= "Cache-Control: no-cache\r\n";
        $out .= "Cookie: $cookie\r\n\r\n";
        $out .= $post;
    } else {
        $out = "GET $script HTTP/1.1\r\n";
        $out .= "Accept: */"."*\r\n";
        $out .= "Referer: $url\r\n";
        $out .= "Accept-Language: zh-cn\r\n";
        $out .= "Accept-Encoding:\r\n";
        $out .= "User-Agent: $agent\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Connection: Close\r\n";
        $out .= "Cookie: $cookie\r\n\r\n";
    }
    $fp = fsockopen($host, $port, $errno, $errstr, $timeout);
    if(!$fp) {
        return false;
    } else {
        fwrite($fp, $out);
        $return = '';
        while(!feof($fp) && $limit > -1) {
            $limit -= 8192;
            $return .= @fread($fp, 8192);
            if(!isset($status)) {
                preg_match("|^HTTP/[^\s]*\s(.*?)\s|",$return, $status);
                $status=$status[1];
                if($status!=200) {
                    return false;
                }
            }
        }
        fclose($fp);
                preg_match("/^Location: ([^\r\n]+)/m",$return,$match);
        if(!empty($match[1]) && $location=$match[1]) {
            if(strpos($location,":/"."/")===false) {
                $location=dirname($url).'/'.$location;
            }
            $args=func_get_args();
            $args[0]=$location;
            return call_user_func_array("dfopen",$args);
        }
        if(false!==($strpos = strpos($return, "\r\n\r\n"))) {
            $return = substr($return,$strpos);
            $return = preg_replace("~^\r\n\r\n(?:[\w\d]{1,8}\r\n)?~","",$return);
            if("\r\n\r\n"==substr($return,-4)) {
                $return = preg_replace("~(?:\r\n[\w\d]{1,8})?\r\n\r\n$~","",$return);
            }
        }

        return $return;
    }
}

function xml2array($xmlString = '')
     {
      $targetArray = array();
      $xmlObject = simplexml_load_string($xmlString);
      $mixArray = (array)$xmlObject;
      foreach($mixArray as $key => $value)
      {
       if(is_string($value))
       {
        $targetArray[$key] = $value;
       }
       if(is_object($value))
       {
        $targetArray[$key] = xml2array($value->asXML());
       }
       if(is_array($value))
       {
        foreach($value as $zkey => $zvalue)
        {
            if(is_object($zvalue)){
                if(is_numeric($zkey))
                 {
                  $targetArray[$key][] = xml2array($zvalue->asXML());
                 }
                 if(is_string($zkey))
                 {
                  $targetArray[$key][$zkey] = xml2array($zvalue->asXML());
                 }
            }else{
                if(is_string($zkey))
                 {
                  $targetArray[$key][$zkey] = ($zvalue );
                 }

            }
         
        }
       }
      }
      return $targetArray;

     }


 //随机生成代码
//$num 位数
//$type 代码类型 0：数字 ，1：大写字母+数字 

function createCheckCode($num,$type=1)  {
    //if($type===''){$type=1;}
    $asc_number='';
    for($i=0;$i <$num;$i++)  {  
        
        $number = rand(0,$type);
        switch($number)  { 

            case 0: $rand_number = rand(48,57); break;//数字 
            case 1: $rand_number = rand(65,90);break;//大写字母 
            case 3: $rand_number = rand(97,122);break;//小写字母 
         } 

        $asc = sprintf("%c",$rand_number);
        $asc_number =$asc_number. $asc;
    } 
    return $asc_number; 

}  


/**

* 数字转换为中文

* @param  string|integer|float  $num  目标数字

* @param  integer $mode 模式[true:金额（默认）,false:普通数字表示]

* @param  boolean $sim 使用小写（默认）

* @return string

*/

 function number2chinese($num,$mode = true,$sim = true){

    if(!is_numeric($num)) return '含有非数字非小数点字符！';

    $char    = $sim ? array('零','一','二','三','四','五','六','七','八','九')

    : array('零','壹','贰','叁','肆','伍','陆','柒','捌','玖');

    $unit    = $sim ? array('','十','百','千','','万','亿','兆')

    : array('','拾','佰','仟','','萬','億','兆');

    $retval  = $mode ? '元':'';

    //小数部分

    if(strpos($num, '.')){

        list($num,$dec) = explode('.', $num);

        $dec = strval(round($dec,2));

        if($mode){

            $retval .= "{$char[$dec['0']]}角{$char[$dec['1']]}分";

        }else{

            for($i = 0,$c = strlen($dec);$i < $c;$i++) {

                $retval .= $char[$dec[$i]];

            }

        }

    }

    //整数部分

    $str = $mode ? strrev(intval($num)) : strrev($num);

    for($i = 0,$c = strlen($str);$i < $c;$i++) {

        $out[$i] = $char[$str[$i]];

        if($mode){

            $out[$i] .= $str[$i] != '0'? $unit[$i%4] : '';

                if($i>1 and $str[$i]+$str[$i-1] == 0){

                $out[$i] = '';

            }

                if($i%4 == 0){

                $out[$i] .= $unit[4+floor($i/4)];

            }

        }

    }

    $retval = join('',array_reverse($out)) . $retval;

    return $retval;

 }
?>