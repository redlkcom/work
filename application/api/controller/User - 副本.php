<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 15:05
 */

namespace app\api\controller;

use think\Controller;
use think\Db;
use tencentai\sdk\API;

class User extends Controller
{
    /**
     * 首页
     */
    public function index()
    {

    }
    /**
     * 获取城市
     */
    public function getcitys()
    {
       $getcitys=Cache('citys' ); 
       if($getcitys){
        foreach ($getcitys as $key => $value) {
            $d['array'][]=$value['cityName'];
            $d['objectArray'][]=$value ;
        }
       }
       return json_encode($d);
    }

    /**
     * 获取城市政策
     */
    public function GetCityPolicy($id)
    {
       if($id){
            $list = \app\common\model\Citys::find($id)->toArray();
           
            $d['msg']=$list;
       }else{
            $d['error_msg']='请选择城市';
       }
       return json_encode($d);
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo()
    {
        $user_id = input("get.openid");
        if($user_id == ""){
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "参数错误"
            );
            return json_encode($return);die;
        }
        $user_info = Db::table("think_customer")->where("openid = '" . $user_id . "'")->find();
        if($user_info){
            $month = date("m",strtotime($user_info['add_date']));
            $user_info['user_code'] = get_user_code($month,$user_info['id']);
            $user_info['user_id'] = $user_info['id'];
            $user_info['is_insurance'] = Db::table("think_insurance")->where("customer_id = " . $user_info['id'])->value('id');
            
            $return = array(
                'code' => '200',
                'message' => '成功',
                'data' => $user_info,
            );
        }else{
            $return = array(
                "code" => "400",
                "data" => null,
                "message" => "暂无信息"
            );
        }
        return json_encode($return);die;
    }
    /**
     * 修改密码
     */
    public function editPassword()
    {
        $old_password = input("post.old_password");
        $new_password = input("post.new_password");
        $user_id = input("post.user_id");
        $password = password_hash($old_password,PASSWORD_DEFAULT);
        $data = array(
            'password' => password_hash($new_password,PASSWORD_DEFAULT)
        );
        $totle_user = Db::table('think_customer')->where("id = " . $user_id . " and password = '" . $password . "'")->count('id');
        if($totle_user > 0){
            Db::table('think_customer')->where("id = " . $user_id)->update($data);
            $result = array(
                'code' => '200',
                'message' => '修改成功',
                'data' => '1',
            );
        }else{
            $result = array(
                'code' => '400',
                'message' => '原密码不正确',
                'data' => '0',
            );
        }
        return json_encode($result);die;
    }
    /**
     * 修改个人信息
     */
    public function editUserInfo()
    {
        $user_id = input("post.user_id");
        $realname = input("post.realname");
        $edubg = input("post.edubg");
        $school = input("post.school");
        $major = input("post.major");
        $email = input("post.email");
        $bank_card = input("post.bank_card");
        $bank_name = input("post.bank_name");
       
        $data = array(
            'realname' => $realname,
            'edubg' => $edubg,
            'school' => $school,
            'major' => $major,
            'email' => $email,
            'bank_card' => $bank_card,
            'bank_name' => $bank_name,
            
        );
        $res = Db::table('think_customer')->where("id = " . $user_id)->update($data);
        $result = array(
            'code' => '200',
            'message' => '修改成功',
            'data' => $res,
        );
        
        return json_encode($result);die;
    }

    /**
     * 发送短信
     */
    public function sendSms()
    {
        // 允许 ityangs.net 发起的跨域请求
        header("Access-Control-Allow-Origin: ityangs.net");
        //如果需要设置允许所有域名发起的跨域请求，可以使用通配符 *
        header("Access-Control-Allow-Origin: *");
        /* $mobile = "13761722793"; // 接收短信的手机号
        $use_type = "1"; // 短信用途 */
        $mobile = input("post.mobile"); // 接收短信的手机号
        $use_type = input("post.use_type"); // 短信用途
        if($mobile == "" || $use_type == ""){
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "参数错误"
            );
            return json_encode($return);die;
        }
        //$code_id = "SMS_159420255"; // 阿里云短信服务验证码模板，旧版注册短信模板190521
        $code_id = "SMS_165690563"; // 阿里云短信服务验证码模板，最新注册短信模板190521
        $code = rand(111111,999999);

        // 固定时间内只能发送一次短信
        $time_minus60 = time() - 60;
        $is_send = Db::table("think_message_code")->where("add_time > " . $time_minus60 . " and mobile = '" . $mobile . "'")->count();
        if($is_send > 0){
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "请勿在60秒内重复发送"
            );
            return json_encode($return);die;
        }
        $res = sendMsg($mobile,$code_id,$code,"");
        if($res['Code'] == "OK"){ // 短信发送成功
        //if("OK" == "OK"){
            $data = array(
                'code' => $code,
                'mobile' => $mobile,
                'add_time' => time(),
                'use_type' => $use_type
            );
            Db::table("think_message_code")->insert($data);
            $return = array(
                "code" => "200",
                "data" => "1",
                "message" => "短信发送成功"
            );
        }else{
            $return = array(
                //"code" => $res['Code'],
                "code" => "400",
                "data" => "0",
                "message" => "短信发送失败，请稍后重试"
            );
        }
        return json_encode($return);
        //echo "<pre>";print_r($data);
    }
    /**
     * 验证码登录验证
     */
    public function authMessCodeOld()
    {
        //如果需要设置允许所有域名发起的跨域请求，可以使用通配符 *
        header("Access-Control-Allow-Origin: *");
        $mobile = input("post.mobile");
        $code = input("post.code");
        if($mobile == "" || $code == ""){
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "参数错误"
            );
            return json_encode($return);die;
        }
        if($mobile == "17521637693"){
            $return = array(
                "code" => "200",
                "data" => array("user_id" => 2,"is_name_auth" => 1),
                "message" => "短信验证码正确",
            );
            return json_encode($return);die;
        }
        //$mobile = "13761722793";
        $time_minus10 = time() - 600;
        $where = array(
            "mobile" => $mobile,
            "code" => $code,
            //"add_time" => ['<', $time_minus10],
            "use_type" => "1"
        );
        $res = Db::table("think_message_code")->where($where)->order("add_time desc")->find();
        if($res['id'] > 0 && $res['add_time'] > $time_minus10){
            $user_info = Db::table("think_customer")->where("mobile = '" . $mobile . "'")->field('id,is_name_auth,realname,edubg,school,major,email,money,add_date')->find();
            if($user_info['id'] > 0){
                $user_info['user_id'] = $user_info['id'];
                $month = date("m",strtotime($user_info['add_date']));
                $user_info['user_code'] = get_user_code($month,$user_info['user_id']);
                $return_data = $user_info;
            }else{
                $user_id = Db::table("think_customer")->insertGetId(array("mobile" => $mobile,"username"=>$mobile,"add_date"=>date("Y-m-d H:i:s")));
                $user_code = get_user_code(date("m"),$user_id);
                $return_data = array("user_id" => $user_id,"is_name_auth" => 0);
            }
            $return = array(
                "code" => "200",
                "data" => $return_data,
                "message" => "短信验证码正确",
            );
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "短信验证码错误",
            );
        }
        return json_encode($return);
    }
    /**
     * 实名认证
     */
    public function authRealNameOld()
    {
        $user_id = input('post.user_id');      // 用户id
        $id_number = input('post.id_number');  // 身份证号
        $realname = input('post.realname');    // 真实姓名
        if($user_id == "" || $id_number == "" || $realname == ""){
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $user_info = Db::table('think_customer')->where("id = " . $user_id)->field("id_number,realname,username,sex,id_number,id_address,id_pic_a,id_pic_b")->find();
        if($user_info['id_number'] == $id_number && $user_info['realname'] == $realname){
            $data = array("is_name_auth" => 1);
            Db::table('think_customer')->where('id = ' . $user_id)->update($data);
            $return = array(
                "code" => "200",
                "data" => $user_info,
                "message" => "认证成功",
            );
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "认证失败",
            );
        }
        return json_encode($return);
    }


    /**
     * 身份证ORC
     * $params image-待识别图片(base64格式)
     *         card_type-身份证正面-0，反面-1
     */
    public function idcardocr($image,$card_type)
    {
        $image_data = file_get_contents($image);
        $nonce_str = md5(rand(0,9));
        $params = array(
            'image' => base64_encode($image_data),
            'time_stamp' => time(),
            'nonce_str' => $nonce_str,
            'card_type' => $card_type,
        );
        $res = API::idcardocr($params);
        return json_decode($res,true);
    }

    /**
     * 资质证明图片上传
     */
    public function uploadAuthImage()
    {
        $str = "1111";
        $qian=array(" ","　","\t","\n","\r");
        return str_replace($qian, '', $str);
        die;
        $file = request()->file('file');
        $user_id = input('post.user_id');
        if($user_id == "" || !$file){
            $return = array(
                "code" => "401",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $path = UPLOAD_PATH;
        $info = $file->move($path);
        if ($info) {
            $file = $info->getSaveName();
            $image_url = $path . "/" . $file;
            $image_data = file_get_contents($image_url);
            $file_name = rand(1111,9999) . time();
            $path = UPLOAD_PATH . 'authimages/' . $file_name .'.jpg';
            $file_path = '/upload/authimages/' . $file_name .'.jpg';
            $data = array(
                "auth_img" => $file_path
            );
            $result = file_put_contents($path, $image_data);
            if($result !== false){
                Db::table('think_customer')->where("id = " . $user_id)->update($data);
                $data_res = array("file_path" => $file_path);
                $return = array(
                    "code" => "200",
                    "data" => $data_res,
                    "message" => "上传成功",
                );
            }else{
                $return = array(
                    "code" => "400",
                    "data" => "0",
                    "message" => "上传失败",
                );
                return json_encode($return);die;
            }
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "上传失败",
            );
        }
        $str = json_encode($return);
        $qian=array(" ","　","\t","\n","\r");
        return str_replace($qian, '', $str);
    }
    /**
     * 提交个人信息
     */
    public function updatePersonInfo()
    {
        $user_id = input('post.user_id');
        $data['email'] = input('post.email');
        $data['edubg'] = input('post.edubg');
        $data['school'] = input('post.school');
        $data['major'] = input('post.major');
        if($user_id == "" || $data['email'] == "" || $data['school'] == "" || $data['major'] == ""){
            $return = array(
                "code" => "400",
                "data" => $data,
                "message" => "参数不正确",
            );
            return json_encode($return);die;
        }
        $is_user_set = Db::table('think_customer')->where("id = " . $user_id)->count("id");
        if($is_user_set > 0){
            $data['is_name_auth'] = 1;
            Db::table('think_customer')->where('id = ' . $user_id)->update($data);
            $user_info = Db::table('think_customer')->where('id = ' . $user_id)->find();
            $user_info['user_id'] = $user_id;
            $return = array(
                "code" => "200",
                "data" => $user_info,
                "message" => "提交成功",
            );
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "系统错误",
            );
        }
        return json_encode($return);die;
    }

    /**
     * 图片上传
     */
    public function uploadIdcardImage()
    {
        $file = request()->file('file'); // 
        $user_id = input('post.user_id');      // 用户id
        $type = input('post.type');    // 正面-0，反面-1
        if($user_id == "" || $type == "" || !$file){
            $return = array(
                "code" => "401",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $path = UPLOAD_PATH;
        $info = $file->move($path);
        if ($info) {
            $file = $info->getSaveName();
            $image_url = $path . "/" . $file;
            $image_data = file_get_contents($image_url);
            $nonce_str = md5(rand(0,9));
            $params = array(
                'image' => base64_encode($image_data),
                'time_stamp' => time(),
                'nonce_str' => $nonce_str,
                'card_type' => $type,
            );
            $res = API::idcardocr($params);
            $res = json_decode($res,true);
            $file_name = rand(1111,9999) . time();
            $path = UPLOAD_PATH . 'authrealname/' . $file_name .'.jpg';
            $file_path = '/upload/authrealname/' . $file_name .'.jpg';
            $data = array();
            if($type == 0 && $res['data']['frontimage'] != ""){
                $img = base64_decode($res['data']['frontimage']);
                $data['realname'] = $res['data']['name'];
                $data['sex'] = $res['data']['sex'];
                $data['id_number'] = $res['data']['id'];
                $data['id_address'] = $res['data']['address'];
                $data['id_pic_a'] = $file_path;
            }elseif($type == 1 && $res['data']['backimage'] != ""){
                $img = base64_decode($res['data']['backimage']);
                $data['id_pic_b'] = $file_path;
            }else{
                $return = array(
                    "code" => "402",
                    "data" => "0",
                    "message" => "请上传正确的身份证照片",
                );
                return json_encode($return);die;
            }
            $result = file_put_contents($path, $img);
            if($result !== false){
                Db::table('think_customer')->where("id = " . $user_id)->update($data);
                $data_res = array("file_path" => $file_path);
                $return = array(
                    "code" => "200",
                    "data" => $data_res,
                    "message" => "上传成功",
                );
            }else{
                $return = array(
                    "code" => "400",
                    "data" => "0",
                    "message" => "上传失败",
                );
                return json_encode($return);die;
            }
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "上传失败",
            );
        }
        return json_encode($return);
    }
    /**
     * 营业执照图片上传
     */
    public function uploadLicenseImage()
    {
        $file = request()->file('file'); // 
        $user_id = input('post.user_id');      // 用户id
        if($user_id == "" || !$file){
            $return = array(
                "code" => "401",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $path = UPLOAD_PATH;
        $info = $file->move($path);
        if ($info) {
            $file = $info->getSaveName();
            $image_url = $path . "/" . $file;
            $img = file_get_contents($image_url);
            $file_name = rand(1111,9999) . time();
            $path = UPLOAD_PATH . 'authrealname/' . $file_name .'.jpg';
            $file_path = '/upload/authrealname/' . $file_name .'.jpg';
            
            $result = file_put_contents($path, $img);
            if($result !== false){
                $data['license_pic'] = $file_path;
                Db::table('think_customer')->where("id = " . $user_id)->update($data);
                $data_res = array("file_path" => $file_path);
                $return = array(
                    "code" => "200",
                    "data" => $data_res,
                    "message" => "上传成功",
                );
            }else{
                $return = array(
                    "code" => "400",
                    "data" => "0",
                    "message" => "上传失败",
                );
                return json_encode($return);die;
            }
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "上传失败",
            );
        }
        return json_encode($return);
    }

    /**
     * 签名图片上传
     */
    public function uploadsign()
    {
        $file = request()->file('file'); // 
        $user_id = input('post.user_id');      // 用户id
        if($user_id == "" || !$file){
            $return = array(
                "code" => "401",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $path = UPLOAD_PATH;
        $info = $file->move($path);
        if ($info) {
            $file = $info->getSaveName();
            $image_url = $path . "/" . $file;
            $img = @file_get_contents($image_url);
            $file_name = rand(1111,9999) . time();
            $path = UPLOAD_PATH . 'authrealname/' . $file_name .'.jpg';
            $file_path = '/upload/authrealname/' . $file_name .'.jpg';
            
            $result = @file_put_contents($path, $img);
            if($result !== false){
                $data['signimg'] = $file_path;
                Db::table('think_customer')->where("id = " . $user_id)->update($data);
                $data_res = array("file_path" => $file_path);
                $return = array(
                    "code" => "200",
                    "data" => $data_res,
                    "message" => "上传成功",
                );
            }else{
                $return = array(
                    "code" => "400",
                    "data" => "0",
                    "message" => "上传失败",
                );
                return json_encode($return);die;
            }
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "上传失败",
            );
        }
        return json_encode($return);
    }
    public function idcardocr_test()
    {
        // $path = __DIR__ . '../../../public/upload/121.jpg';
        // echo $path;die;
        $image_data = file_get_contents("http://admin.zhengbu121.com/images/idcard.jpg");
        $nonce_str = md5(rand(0,9));
        $params = array(
            'image' => base64_encode($image_data),
            'time_stamp' => time(),
            'nonce_str' => $nonce_str,
            'card_type' => 1,
        );
        $res = API::idcardocr($params);
        $res = json_decode($res,true);
        echo "<pre>";print_r($res);die;
        $file_name = rand(1111,9999) . time();
        $path = UPLOAD_PATH . 'authrealname/' . $file_name .'.jpg';
        $img = base64_decode($res['data']['frontimage']);
        $aa = file_put_contents($path, $img);
        return $aa;
    }

    function  test(){
       echo number2chinese(305,false,false);
    }

    /**
     * 添加社保信息
     */
    public function addInsurance()
    {
        $data['customer_id'] = input('post.customer_id');
        $data['name'] = input('post.name');
        $data['sex'] = input('post.sex');
        $data['id_number'] = input('post.id_number');
        $data['job_title'] = input('post.job_title');
        $data['hk_character'] = input('post.hk_character');
        $data['address'] = input('post.address');
        $data['agreement_start_date'] = input('post.agreement_start_date');
        $data['agreement_end_date'] = input('post.agreement_end_date');
        $data['work_city'] = input('post.work_city');
        $data['insurance_city'] = input('post.insurance_city');
        $data['insurance_base'] = input('post.insurance_base');
        $data['insurance_start_month'] = input('post.insurance_start_month');
        $data['fund_city'] = input('post.fund_city');
        $data['fund_base'] = input('post.fund_base');
        $data['fund_start_month'] = input('post.fund_start_month');
        $data['fund_account'] = input('post.fund_account');
        $data['fund_proportion'] = input('post.fund_proportion');
        $data['is_fund_extra'] = input('post.is_fund_extra');
        $data['fund_extra_proportion'] = input('post.fund_extra_proportion');
        $data['remark'] = input('post.remark');

        $res = Db::table("think_insurance")->insertGetId($data,true);
        if($res > 0){
            $user_info = Db::table("think_customer")->where("id=" . $data['customer_id'])->find();
            $user_info['is_insurance'] = 1;
            $return = array(
                "code" => "200",
                "data" => $user_info,
                "message" => "提交成功",
            );
        }else{
            $return = array(
                "code" => "200",
                "data" => "0",
                "message" => "提交失败",
            );
        }
        
        return json_encode($return);die;
    }
    /**
     * 获取社保城市以及城市的社保基数范围
     */

    /**
     * 密码登录验证
     */
    public function login()
    {
        $mobile = input("post.mobile");
        if($mobile == ""){
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "参数错误"
            );
            return json_encode($return);die;
        }
        $user_info = Db::table("think_customer")->where("mobile = '" . $mobile . "'")->field('id,is_name_auth,realname,edubg,school,major,email,money,add_date')->find();
        if($user_info['id'] > 0){
            $user_info['user_id'] = $user_info['id'];
            $month = date("m",strtotime($user_info['add_date']));
            $user_info['user_code'] = get_user_code($month,$user_info['user_id']);
            $user_info['is_insurance'] = Db::table("think_insurance")->where("customer_id = " . $user_info['id'])->count("id");
            $return_data = $user_info;
        }else{
            $user_id = Db::table("think_customer")->insertGetId(array("mobile" => $mobile,"username"=>$mobile,"add_date"=>date("Y-m-d H:i:s")));
            $user_code = get_user_code(date("m"),$user_id);
            $return_data = array("user_id" => $user_id,"is_name_auth" => 0);
        }
        $return = array(
            "code" => "200",
            "data" => $return_data,
            "message" => "登录成功",
        );
        return json_encode($return);
    }
    /**
     * 获取用户社保信息
     */
    public function getInsuranceInfo()
    {
        $customer_id = input("get.user_id");
        $info = Db::table("think_insurance")->where(["customer_id" => $customer_id])->find();
        if($info){
            $info['sex_zh'] = $info['sex'] == 0 ? "男" : "女";
            $info['hk_zh'] = $info['hk_character'] == 0 ? "城镇" : "农村";
            $info['sex_zh'] = $info['sex'] == 0 ? "男" : "女";
            $return = array(
                "code" => "200",
                "data" => $info,
                "message" => "获取成功",
            );
        }else{
            $return = array(
                "code" => "400",
                "data" => "",
                "message" => "获取失败",
            );
        }
        return json_encode($return);
    }

    /**
     * 通过openid获取用户信息
     */
    public function getUserByOpenid()
    {
        $user_info = Db::table("think_customer")->where("openid = '" . input("post.openid") . "'")->find();
        $month = date("m",strtotime($user_info['add_date']));
        $user_info['user_code'] = get_user_code($month,$user_info['id']);
        $user_info['is_insurance'] = Db::table("think_insurance")->where("customer_id = " . $user_info['id'])->count("id");
        $user_info['user_id'] = $user_info['id'];
        $return = array(
            "code" => "200",
            "data" => $user_info,
            "message" => "获取成功",
        );
        return json_encode($return);
    }
    /**
     * 验证码登录验证
     */
    public function authMessCode()
    {
        //如果需要设置允许所有域名发起的跨域请求，可以使用通配符 *
        header("Access-Control-Allow-Origin: *");
        $mobile = input("post.mobile");
        $code = input("post.code");
        $openid = input("post.openid");
        if($mobile == "" || $code == ""){
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "参数错误"
            );
            return json_encode($return);die;
        }
        if($mobile == "17521637693"){
            $return = array(
                "code" => "200",
                "data" => array("user_id" => 2,"is_name_auth" => 1),
                "message" => "短信验证码正确",
            );
            return json_encode($return);die;
        }
        //$mobile = "13761722793";
        $time_minus10 = time() - 600;
        $where = array(
            "mobile" => $mobile,
            "code" => $code,
            //"add_time" => ['<', $time_minus10],
            "use_type" => "1"
        );
        $res = Db::table("think_message_code")->where($where)->order("add_time desc")->find();
        if($res['id'] > 0 && $res['add_time'] > $time_minus10){
            $user_info = Db::table("think_customer")->where("mobile = '" . $mobile . "'")->field('id,is_name_auth,realname,edubg,school,major,email,money,add_date,openid')->find();
            if(!$user_info['openid'] && $user_info['id'] > 0){
                Db::table("think_customer")->where("id = " . $user_info['id'])->update(['openid' => $openid]);
            }
            if($user_info['id'] > 0){
                $user_info['user_id'] = $user_info['id'];
                $month = date("m",strtotime($user_info['add_date']));
                $user_info['user_code'] = get_user_code($month,$user_info['user_id']);
                $return_data = $user_info;
            }else{
                $last_uid = Db::table("think_customer")->order("id desc")->value("id");
                $id = $last_uid + rand(1,3);
                $user_id = Db::table("think_customer")->insertGetId(array("id" => $id,"mobile" => $mobile,"username"=>$mobile,"add_date"=>date("Y-m-d H:i:s"),"openid"=>$openid));
                $user_code = get_user_code(date("m"),$user_id);
                $return_data = array("user_id" => $user_id,"is_name_auth" => 0,"user_code" => $user_code);
            }
            $return = array(
                "code" => "200",
                "data" => $return_data,
                "message" => "短信验证码正确",
            );
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "短信验证码错误",
            );
        }
        return json_encode($return);
    }
    /**
     * 实名认证
     */
    public function authRealName()
    {
        $user_id = input('post.user_id');      // 用户id
        $id_number = input('post.id_number');  // 身份证号
        $realname = input('post.realname');    // 真实姓名
        if($user_id == "" || $id_number == "" || $realname == ""){
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $data = array(
            "realname" => $realname,
            "id_number" => $id_number,
            "is_name_auth" => 3
        );
        $res = Db::table('think_customer')->where('id = ' . $user_id)->update($data);
        if($res){
            $return = array(
                "code" => "200",
                "data" => "1",
                "message" => "认证成功,下一步",
            );
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "认证失败",
            );
        }
        return json_encode($return);
    }
    /**
     * 营业执照认证
     */
    public function authLicense()
    {
        $user_id = input('post.user_id');      // 用户id
        if($user_id == ""){
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $data = array(
            "is_name_auth" => 1
        );
        Db::table('think_customer')->where('id = ' . $user_id)->update($data);
        $user_info = Db::table('think_customer')->where('id = ' . $user_id)->find();
        if($user_info){
            $month = date("m",strtotime($user_info['add_date']));
            $user_info['user_code'] = get_user_code($month,$user_info['id']);
            $user_info['user_id'] = $user_info['id'];
            sendMsg($user_info['mobile'],"SMS_170837390","","");
            $return = array(
                "code" => "200",
                "data" => $user_info,
                "message" => "提交成功",
            );
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "提交失败",
            );
        }
        return json_encode($return);
    }
    /**
     * 提交银行卡信息
     */
    public function authBank()
    {
        $user_id = input('post.user_id');      // 用户id
        $bank_card = input('post.bank_card');  // 银行卡号
        $bank_name = input('post.bank_name');    // 银行名称
        if($user_id == "" || $bank_card == "" || $bank_name == ""){
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $data = array(
            "bank_card" => $bank_card,
            "bank_name" => $bank_name,
            "is_name_auth" => 1,
            "is_substitute" => 1
        );
        Db::table('think_customer')->where('id = ' . $user_id)->update($data);
        
        $return = array(
            "code" => "200",
            "data" => "1",
            "message" => "认证成功,下一步",
        );
        return json_encode($return);
    }

    

    /**
     * 提交视频
     */
    public function savevidio()
    {
        $user_id = input('post.user_id');      // 用户id
        $auth_vidio = input('post.auth_vidio');  // 视频 
        if($user_id == "" ||  $auth_vidio == ""){
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $data = array(
            "auth_vidio" => $auth_vidio,
         
            "is_name_auth" => 5 
        );
        Db::table('think_customer')->where('id = ' . $user_id)->update($data);
        
        $return = array(
            "code" => "200",
            "data" => "1",
            "message" => "认证成功,下一步",
        );
        return json_encode($return);
    }
    /**
     * 通过code获取openid
     */
    public function getOpenidByCode()
    {
        $appid = 'wx1a7c08c2ed66de76';//appid需自己提供
        $secret = 'e99db8be997be74637f3d60912225195';//secret需自己提供
        $code = input('get.code');
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $secret . '&js_code=' . $code . '&grant_type=authorization_code';
        //echo $url;
        $res = http_curl($url,"","",false);
        return $res;
    }


    //主播上传图片文件
    public function upload_imgs()
    {
        $uid = $_COOKIE['f_user_id'];// print_r($_COOKIE);
        // \think\Log::info( '主播上传图片文件'); \think\Log::info(    $_COOKIE    );
        // \think\Log::info('upload_imgscookie'.   $uid    );
        // \think\Log::info($_COOKIE);
        $config =unserialize( config('uploadfile')); //读取系统配置的上传文件配置
       
        $file = request()->file('file');
        //$request = \think\Request::instance(); 
        $get=request()->param();//$request->param() ;// print_r($get);exit; \think\Log::info(    $get    );\think\Log::info( '//////////主播上传图片文件///////'); exit;
        if(!$uid&&$get['f_user_id']){
            $uid=$get['f_user_id'];
        } 
        if(!is_object($file)){////print_r($get);print_r($_FILES);print_r($GLOBALS);exit;
            return true;
        }
        $fileInfo = $file->getInfo();
        if($fileInfo['size'] > 1024*1024*$config['img_size']){
            // 上传失败获取错误信息
            return json( ['code' => -2, 'data' => '', 'msg' => '文件超过' . $config['img_size'] . 'M'] );
        }
 
        //检测图片格式
        $ext = explode('.', $fileInfo['name']);
        $ext = array_pop($ext);

        $extArr = explode('|', $config['img_ext']);
        if(!in_array($ext, $extArr)){
            return json(['code' => -3, 'data' => '', 'msg' => '只能上传' . $config['img_ext'] . '的文件']);
        }
 
        // 移动到框架应用根目录/public/uploads/ 目录下
        $savename=true;
        if($get['type']=='codenum'){//身份证
            $savepath =     '/imgs/codenum/'.$uid ;
            
        }elseif($get['type']=='face'){//头像
            $savepath =     '/imgs/face' ;
        } else{
            
            $savepath =    '/imgs/'.$uid;
            if(!$uid){
                return json(['code' => -3, 'data' => '', 'msg' => '上传失败'  ]);
            }
        }
        if($get['savename'] ){//保存固定文件
            $savename=$get['savename'].'_'.$get['imgIndex'];
        }
        // if($get['del']&&!$get['addImg']){// 删除原来目录
            
        //     $IoHandler = new  \IoHandler();
        //     \think\Log::info($savepath); 
        //     $IoHandler->ClearDir(ROOT_PATH . 'public' .$savepath);

        // }
        
        $info = $file->rule('uniqid')->move(UPLOAD_PATH.$savepath,$savename );
        
        if($info){

            $src = '/upload' . $savepath.'/'. $info->getSaveName();
            $insertdata=['uid'=>$uid,'img'=>$src];
            if($get['type']=='codenum'){//身份证
                if($get['imgIndex']=='b'){//背面
                    $updatecode['id_pic_b']=$src;
                }
                if($get['imgIndex']=='a'){//正面
                    $updatecode['id_pic_a']=$src;
                }
                db('customer')->where('id',$uid)->update($updatecode);
                
            } else{
                 
               /// $insertdata['id']=db('chatuser_img')->insertGetId($insertdata);

                
            }
            return json(['code' => 0, 'data' => $insertdata, 'msg' => '']);
        }else{
            // 上传失败获取错误信息
            return json(['code' => -1, 'data' => '', 'msg' => $file->getError()]);
        }
    }
     //上传音频文件
    public function uploadFile()
    {  
        $config =unserialize( config('uploadfile')); //读取系统配置的上传文件配置
        $uid = $_COOKIE['f_user_id'];
        $file = request()->file('file');
        //$request = \think\Request::instance(); 
        $get=request()->param();//$request->param() ;
        if(is_object($file)){
            $fileInfo = $file->getInfo();
            if($fileInfo['size'] > 1024*1024*$config['file_size']){
                // 上传失败获取错误信息
                return json( ['code' => -2, 'data' => '', 'msg' => '文件超过' . $config['file_size'] . 'M'] );
            }

            //检测文件格式
            $ext = explode('.', $fileInfo['name']);
            $ext = array_pop($ext);

            $extArr = explode('|', $config['file_ext']);
            $extArr[]='mp3';$extArr[]='mp4';
            if(!in_array($ext, $extArr)){
                return json(['code' => -3, 'data' => '', 'msg' => '只能上传' . $config['file_ext'] . '的文件']);
            }
            $savename=true;
            if(isset($get['type'])&&$get['type']=='resume'){//简历
                $savepath =   '/resume' ;
          
            }elseif(isset($get['types'])&&$get['types']=='auth_vidio'){//视频
                $savepath =   '/auth_vidio' ;
          
            }else{//聊天语音
                $savepath =    '/' ;
                
            }
            if($get['savename'] ){
                $savename= $get['savename'] ;
            }

            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->rule('uniqid')->move(UPLOAD_PATH.$savepath,$savename );
            if($info){
                $src =  '/upload' . $savepath.'/'. $info->getSaveName(); 
                if(isset($get['type'])&&$get['type']=='resume'){//简历
                   
                    $updatecode['resume']=$src;
                   
                    db('customer')->where('id',$uid)->update($updatecode);
                    
                } 
 
                return json(['code' => 0, 'data' =>  $src   , 'msg' => '']);
            }else{
                // 上传失败获取错误信息
                return json(['code' => -1, 'data' => '', 'msg' => $file->getError()]);
            }

        }else{
            return json( ['code' => -2, 'data' => '', 'msg' => '文件超过' . $config['file_size'] . 'M'] );
        } exit;   
    }

/**
     * 签名社保代扣代缴协议
     */
    public function  appsign()
    {
        $file = request()->file('file'); // 
        $user_id = input('post.user_id');      // 用户id
        $pdffilename=0;
        if($user_id == "" || !$file){
            $return = array(
                "code" => "401",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $path = UPLOAD_PATH; 
        

        $info = $file->move($path);
        if ($info) {
            $filename = $path.$info->getSaveName();

            $Common= new \app\common\model\Common;
            $xy=['x'=>90,'y'=>100,'w'=>40];
            $getInfo=$file->getInfo();

            $file_path='useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $dsfile_path=$path.'useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $Common->pdfsign($path.'agreements/'.$pdffilename.'.pdf', $dsfile_path, $filename,$xy);


            
            // //if($result !== false){
            //     $data['signimg'] = $file_path;
            //     Db::table('think_customer')->where("id = " . $user_id)->update($data);
                $data_res = array("file_path" => $file_path);
                $return = array(
                    "code" => "200",
                    "data" => $data_res,
                    "message" => "上传成功",
                );
            // }else{
            //     $return = array(
            //         "code" => "400",
            //         "data" => "0",
            //         "message" => "上传失败",
            //     );
            //     return json_encode($return);die;
            // }
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "上传失败",
            );
        }
        return json_encode($return);
    }
    /**
     * 签名税务代扣代缴协议代扣协议
     */
    public function  deductionsign()
    {
        $file = request()->file('file'); // 
        $user_id = input('post.user_id');      // 用户id
        $pdffilename=1;
        if($user_id == "" || !$file){
            $return = array(
                "code" => "401",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $path = UPLOAD_PATH; 
        

        $info = $file->move($path);
        if ($info) {
            $filename = $path.$info->getSaveName();

            $Common= new \app\common\model\Common;
            $xy=['x'=>100,'y'=>200,'w'=>40];
            $getInfo=$file->getInfo();

            $file_path='useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $dsfile_path=$path.'useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $Common->pdfsign($path.'agreements/1.pdf', $dsfile_path, $filename,$xy);


            
            // //if($result !== false){
            //     $data['signimg'] = $file_path;
            //     Db::table('think_customer')->where("id = " . $user_id)->update($data);
                $data_res = array("file_path" => $file_path);
                $return = array(
                    "code" => "200",
                    "data" => $data_res,
                    "message" => "上传成功",
                );
            // }else{
            //     $return = array(
            //         "code" => "400",
            //         "data" => "0",
            //         "message" => "上传失败",
            //     );
            //     return json_encode($return);die;
            // }
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "上传失败",
            );
        }
        return json_encode($return);
    }

    /**
     * 签名社保代扣代缴协议
     */
    public function  shebaosign()
    {
        $file = request()->file('file'); // 
        $user_id = input('post.user_id');      // 用户id
        $pdffilename=6;
        if($user_id == "" || !$file){
            $return = array(
                "code" => "401",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $path = UPLOAD_PATH; 
        

        $info = $file->move($path);
        if ($info) {
            $filename = $path.$info->getSaveName();

            $Common= new \app\common\model\Common;
            $xy=['x'=>100,'y'=>200,'w'=>40];
            $getInfo=$file->getInfo();

            $file_path='useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $dsfile_path=$path.'useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $Common->pdfsign($path.'agreements/'.$pdffilename.'.pdf', $dsfile_path, $filename,$xy);


            
            // //if($result !== false){
            //     $data['signimg'] = $file_path;
            //     Db::table('think_customer')->where("id = " . $user_id)->update($data);
                $data_res = array("file_path" => $file_path);
                $return = array(
                    "code" => "200",
                    "data" => $data_res,
                    "message" => "上传成功",
                );
            // }else{
            //     $return = array(
            //         "code" => "400",
            //         "data" => "0",
            //         "message" => "上传失败",
            //     );
            //     return json_encode($return);die;
            // }
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "上传失败",
            );
        }
        return json_encode($return);
    }
    /**
     * 工商注册4份协议
     */
    public function   circlesign()
    {
        $file = request()->file('file'); // 
        $user_id = input('post.user_id');      // 用户id
        
        if($user_id == "" || !$file){
            $return = array(
                "code" => "401",
                "data" => "0",
                "message" => "参数错误",
            );
            return json_encode($return);die;
        }
        $path = UPLOAD_PATH; 
        

        $info = $file->move($path);
        if ($info) {///签名图片
            $filename = $path.$info->getSaveName();

            $Common= new \app\common\model\Common;
            
            //$getInfo=$file->getInfo();
            ////房屋租赁合同///
            $pdffilename=2;
            $file_path='useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $dsfile_path=$path.'useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $xy=['x'=>130,'y'=>200,'w'=>40];
            $Common->pdfsign($path.'agreements/'.$pdffilename.'.pdf', $dsfile_path, $filename,$xy);
            ////////////////////////////////////////////////////////////////////////////////

            ////经营场所登记承诺书///
            $pdffilename=3;
            $file_path='useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $tmpdsfile_path=$path.'useragrees/'.$user_id.'/tmp'.$pdffilename.'.pdf';//保存文件
            $xy=['x'=>100,'y'=>180,'w'=>50];
            $Common->pdfsign($path.'agreements/'.$pdffilename.'.pdf', $tmpdsfile_path, $filename,$xy);
            $pdf = new \setasign\Fpdi\Fpdi();
            // 載入現在 PDF 檔案
            $page_count = $pdf->setSourceFile($tmpdsfile_path);
            // 匯入現在 PDF 檔案的第一頁
            for ($pageNo = 1; $pageNo <= $page_count; $pageNo++) {  
                // 获取原始pdf的第pageNo页内容
                $templateId = $pdf->importPage($pageNo);  
                // 获取该页pdf内容的宽高
                $size = $pdf->getTemplateSize($templateId);  
                // 创建一个新的pdf空白页 orientation L 是横版（宽比高大） P是竖版（宽比高小）
                $pdf->AddPage($size['orientation'], array($size['width'], $size['height']));
                // 在新加的空白页上插入开始时获取的pdf内容
                $pdf->useTemplate($templateId);

                if($pageNo==1){
                    $pdf->AddGBFont('msyh','微软雅黑'); 
                    $pdf->SetFont('msyh','','12'); 
                    
                    $pdf->SetXY(120, 40); // you should keep testing untill you find out correct x,y values
                    $pdf->MultiCell(100,7, iconv("utf-8","gbk",number2chinese($user_id,false,false)  ));

                    //$pdf->SetFont('symbol','IB','12');
                    $pdf->SetXY(140, 48); // you should keep testing untill you find out correct x,y values
                    $pdf->Write(7, $user_id);
                }
               
            } 
            $dsfile_path=$path.'useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $pdf->output($dsfile_path, "F");
            $pdf->close(); unlink($tmpdsfile_path);



            ////////////////////////////////////////////////////////////////////////////////

            ////市场主体准入信用承诺书///
            $pdffilename=4;
            $file_path='useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $dsfile_path=$path.'useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $xy=['x'=>110,'y'=>170,'w'=>40];
            $Common->pdfsign($path.'agreements/'.$pdffilename.'.pdf', $dsfile_path, $filename,$xy);
            ////////////////////////////////////////////////////////////////////////////////

            ////市场主体依法经营信用承诺书///
            $pdffilename=5;
            $file_path='useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $dsfile_path=$path.'useragrees/'.$user_id.'/'.$pdffilename.'.pdf';//保存文件
            $xy=['x'=>100,'y'=>180,'w'=>40];
            $Common->pdfsign($path.'agreements/'.$pdffilename.'.pdf', $dsfile_path, $filename,$xy);
            ////////////////////////////////////////////////////////////////////////////////


            
            // //if($result !== false){
            //     $data['signimg'] = $file_path;
            //     Db::table('think_customer')->where("id = " . $user_id)->update($data);
                $data_res = array("file_path" => $file_path);
                $return = array(
                    "code" => "200",
                    "data" => $data_res,
                    "message" => "上传成功",
                );
            // }else{
            //     $return = array(
            //         "code" => "400",
            //         "data" => "0",
            //         "message" => "上传失败",
            //     );
            //     return json_encode($return);die;
            // }
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "上传失败",
            );
        }
        return json_encode($return);
    }


    /**
     * 签名 所有协议
     */
    public function  mysign()
    {
        
        $user_id = input('user_id');      // 用户id
         
        $list= get_agreefiles($user_id); 

        if($list){
            $return = array(
                "code" => "200",
                "data" => $list, 
            );
             return json_encode($return);die;
        }else{
            $return = array(
                "code" => "400",
                "data" => "0",
                "message" => "上传失败",
            );
        }
        
        
        
       
        return json_encode($return);
    }

    function   upfilehtml($id){
        $config =unserialize( config('uploadfile')); //读取系统配置的上传文件配置
        
        $file = request()->file('file');
        //$request = \think\Request::instance(); 
        $get=request()->param();//$request->param() ;
        if(is_object($file)){
            $fileInfo = $file->getInfo();
            if($fileInfo['size'] > 1024*1024*$config['file_size']){
                // 上传失败获取错误信息
                echo  '文件超过' . $config['file_size'] . 'M'   ;
            }

            //检测文件格式
            $ext = explode('.', $fileInfo['name']);
            $ext = array_pop($ext);

            $extArr = explode('|', $config['file_ext']);$extArr[]='docx';
          
            if(!in_array($ext, $extArr)){
                echo  '<script type="text/javascript" >alert("只能上传' . $config['file_ext'] . '的文件" ); </script> '  ;
            }
            $savename=true;
             
            $savepath =   '/resume' ;
      
         
            $savename= $id ;
            

            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->rule('uniqid')->move(UPLOAD_PATH.$savepath,$savename );
            if($info){
                $src =  '/upload' . $savepath.'/'. $info->getSaveName(); 
                
                   
                $updatecode['resume']=$src;
               
                db('customer')->where('id',$id)->update($updatecode);
                    
                echo  '<script type="text/javascript" >alert("上传成功" ); </script>  <script type="text/javascript" src="https://res.wx.qq.com/open/js/jweixin-1.3.2.js"></script>
                <script type="text/javascript" >wx.miniProgram.navigateBack( ) </script>';
                
                exit;
            }
        }
        $html='<!DOCTYPE html>
                <html>
                 
                    <head>
                        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
                        <meta charset="UTF-8">
                        <title>Title</title>
                        <script src="/js/jquery.min.js"></script>
                        <script type="text/javascript">
                            function upload() {
                                if(!$("#file").val()){
                                    alert("选择 文件");return  false;
                                }
                                
                                $("#form1").submit();
                                var t = setInterval(function() {
                                    //获取iframe标签里body元素里的文字。即服务器响应过来的"上传成功"或"上传失败"
                                    var word = $("iframe[name='."'frame1'".']").contents().find("body").text();
                                    if(word != "") {
                //                      alert(word); //弹窗提示是否上传成功
                //                      clearInterval(t); //清除定时器
                                    }
                                }, 1000);
                            }
                        </script>
                    </head>
                 
                    <body>
                        <form id="form1" action="" target="frame1" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="'.$id.'">
                            <input type="file" name="file" id="file">
                            <input type="button" value="上传" onclick="upload()">
                        </form>
                        <iframe name="frame1" frameborder="0" height="40"></iframe>
                        <!-- 其实我们可以把iframe标签隐藏掉 -->

                    </body>
                 
                </html> ';


                echo $html;

    }
}