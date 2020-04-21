<?php

namespace app\api\controller;

use auth\Auth;
use think\Db;
use think\Controller;
use app\admin\model\Customer;
use app\admin\traits\Result;
class Insurance extends Controller
{
    function test(){
        $pfbanklogic= new \app\common\logic\pfbanklogic;

        $pfbanklogic->pay8800();

       // $pfbanklogic->pay8805(1);
    }
    /**
     * 增员
     */
    public function customerPlusResult()
    {
        $put = "zy:";
        $put .= file_get_contents('php://input');
        //$put = json_decode($put,true);
        // if(!isset($put['result'])){
        //     $result = array(
        //         'errCode' => '1',
        //         'message' => '返回信息有误',
        //         'data' => "cw1",
        //     );
        //     return json_encode($result);die;
        // }
        // if($put['result'] == 0){
        //     // 增员失败执行的操作

        // }elseif($put['result'] == 1){
        //     // 增员成功进行的操作

        // }elseif($put['result'] == 2 && $put['dataJson'] != ""){
        //     $data = json_decode($put['dataJson'],true);
        //     // 需要补充信息的操作

        // }else{
        //     $result = array(
        //         'errCode' => '1',
        //         'message' => '返回信息有误',
        //         'data' => "cw2",
        //     );
        //     return json_encode($result);die;
        // }
        //echo "<pre>";print_r($put);die;
        // $filename = 'file.txt';

        // $word = "";  //双引号会换行 单引号不换行
        // foreach($put as $k => $v){
        //     $word .= $k . ":" . $v . "\r\n";
        // }
        // $word .= time() . "\r\n";
        $put .= time() . "\r\n";
        $filename = 'file.txt';
        file_put_contents($filename, $put,FILE_APPEND|LOCK_EX);
        $result = array(
            'errCode' => '0',
            'message' => '返回成功',
            'data' => "",
        );
        return json_encode($result);die;
    }
    /**
     * 减员
     */
    public function customerMultiphyResult()
    {
        $put = "jy:";
        $put .= file_get_contents('php://input');
        $put .= time() . "\r\n";
        $filename = 'file.txt';
        file_put_contents($filename, $put,FILE_APPEND|LOCK_EX);
        //$put = json_decode($put,true);
        // if(!isset($_POST['success'])){
        //     $result = array(
        //         'errCode' => '1',
        //         'message' => '返回信息有误',
        //         'data' => "",
        //     );
        //     return json_encode($result);die;
        // }
        // if($_POST['success'] == true){
        //     // 减员成功操作

        // }else{
        //     // 减员失败操作

        // }
        // $filename = 'file.txt';

        // $word = "";  //双引号会换行 单引号不换行
        // foreach($put as $k => $v){
        //     $word .= $k . ":" . $v . "\r\n";
        // }
        // $word .= time() . "\r\n";
        // file_put_contents($filename, $word,FILE_APPEND|LOCK_EX);
        $result = array(
            'errCode' => '0',
            'message' => '返回成功',
            'data' => "",
        );
        return json_encode($result);die;
    }

    function  license(){
        $res=false;
        if ($this->request->isPost()) {
            $Common= new \app\common\model\Common;
            $input =   file_get_contents('php://input'); 
            $data =   $this->request->post(); 
            $file = request()->file('file') ; 
            if($data['type']=='c'){//创客
                $purl='/upload/authrealname/';  
                $path = UPLOAD_PATH.'authrealname/';  
                if(isset($file )){
                    $info = $file->move($path);////print_r($info );exit;
                    if ($info) {///营业执照
                        $userdata['license_pic']= $purl.$info->getSaveName();
                        $userdata['circle_regist']=1;//等待A端上传营业执照后会自动变为是。变为”是“后判断创客可接单 
                        $res = Customer::update($userdata, ['id' => $data['id']]);

                        $Common->Add_data(['uid'=>$data['id'],'name'=>'A端上传营业执照','type'=>1],'log');//日志

                    }
                }
            }elseif($data['type']=='b'){//B端
                $purl='/upload/xy_pic/';  
                $path = UPLOAD_PATH.'xy_pic/';  //print_r(  $data );
                if(isset($file )){
                    $info = $file ->move($path);
                    if ($info) {///合作协议
                        $userdata['xy_pic']= $purl.$info->getSaveName(); 
                        $userdata['id']= $data['id'];
                        $res = \app\admin\service\CompanyService::edit($userdata);
                    }
                }
            }
            
            @file_put_contents($path.'lgo.log', print_r($input,true)  .print_r($data,true)  .print_r($file,true)  ."\t\n" ,FILE_APPEND);
            
            

        }
        if ($res) {
            $msg = Result::success('编辑成功' );
        } else {
            $msg = Result::error('编辑失败');
        }
        echo json_encode( $msg,JSON_UNESCAPED_UNICODE) ;
    }
}
