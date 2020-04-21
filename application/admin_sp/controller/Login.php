<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/16
 * Time: 15:24
 */

namespace app\admin_sp\controller;

use think\Controller;
use app\admin_sp\service\CompanyService;
use app\admin_sp\model\Company as CompanyModel;
use app\admin_sp\model\Customer;

class Login extends Controller
{
    /**
     * 用户登录
     * @return array|mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login()
    {
        if (get_user_id_sp()) {
            $this->redirect(url('/admin_sp/index/index'));
        } else {
            if (!request()->isPost()) {
                //echo password_hash("12345678",PASSWORD_DEFAULT);die;
                return $this->fetch();
            } else {
                $data = input();
                $result = CompanyService::login($data);
                return $result;
            }
        }

    }

    /**
     * 用户注册
     * @return array|mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function register()
    {

        if (get_user_id_sp()) {
            $this->redirect(url('/admin_sp/index/index'));
        } else {
            if (!request()->isPost()) {
                //echo password_hash("12345678",PASSWORD_DEFAULT);die;
                return $this->fetch();
            } else {
                $data = input();
                $result = CompanyService::register($data);
                return $result;
            }
        }

    }


    /**
     * 完善信息
     * @return array|mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function completion_info()
    {
        if (get_user_id_sp()) {
            $this->redirect(url('/admin_sp/index/index'));
        } else {
            if (!request()->isPost()) {
                $companyInfo = CompanyModel::get($_GET['id']);
                $customerList = Customer::where('is_name_auth = 1')->field("id,realname,mobile,add_date")->select()->toArray();
                // $taxClass = [
                //     ["code"=>"101","name"=>"一般纳税人","percentage"=>"0.6"],
                //     ["code"=>"201","name"=>"小规模纳税人","percentage"=>"0.6"]
                // ];
                $taxClass = [
                    ["code"=>"101","name"=>"制造业、批发和零售业","percentage"=>"0.3"],
                    ["code"=>"201","name"=>"交通运输业（不含货运)","percentage"=>"0.3"],
                    ["code"=>"202","name"=>"交通运输-货运","percentage"=>"1.5"],
                    ["code"=>"301","name"=>"娱乐业","percentage"=>"1.5"],
                    ["code"=>"401","name"=>"体育业","percentage"=>"0.3"],
                    ["code"=>"501","name"=>"异地建筑施工","percentage"=>"0.4"],
                    ["code"=>"601","name"=>"其他行业","percentage"=>"0.8"]
                ];
                $this->assign('companyInfo',$companyInfo);
                $this->assign('customerList',$customerList);
                $this->assign('taxClass',$taxClass);
                return $this->fetch();
            } else {
                $data = input();
                $result = CompanyService::save_info($data);
                return $result;
            }
        }

    }

    /**
     * 用户退出
     * @return array
     * @author 原点 <467490186@qq.com>
     */
    public function logout()
    {
        session('user_auth_sp', null);
        session('user_auth_sign_sp', null);
        return ['msg' => '退出成功', 'url' => url('/admin_sp/login/login')];
    }

    /**
     * 解锁
     */
    public function unlock()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $uid = get_user_id_sp();
        if (!$uid) {
            $this->error('登录信息过期', url('/admin_sp/login/login'));
        }
        $password = input('password', '', 'trim');

        $psd = CompanyModel::where('id', '=', get_user_id_sp())->value('password');
        if (md5($password) == $psd) {
            $this->success('解锁成功');
        } else {
            $this->error('密码错误');
        }
    }

    /**
     * 上传头像
     * @return string 图片存储路径
     * @author Dustin Zheng
     */
    public function uploadImg()
    {
        header("content-type:text/html;charset=utf-8");
        //上传excel文件
        $file = request()->file('file');
        //将文件保存到public/uploads目录下面
        $info = $file->validate(['size'=>52428800,'ext'=>'jpg,png,gif'])->move( './uploads/companyLogo');
        if($info){
            //获取上传到后台的文件名
            $fileName = $info->getSaveName();
            //获取文件路径
            $filePath = '/uploads/companyLogo'.DIRECTORY_SEPARATOR.$fileName;
        }else{
            $this->error('文件过大或格式不正确导致上传失败-_-!');
        }
        return json(array('code' => 1, 'errmsg' => '上传成功', 'data'=>$filePath));
    }
    /**
     * 上传营业执照
     * @return string 图片存储路径
     * @author Dustin Zheng
     */
    public function uploadImg2()
    {
        header("content-type:text/html;charset=utf-8");
        //上传excel文件
        $file = request()->file('file');
        //将文件保存到public/uploads目录下面
        $info = $file->validate(['size'=>52428800,'ext'=>'jpg,png,gif'])->move( './uploads/License');
        if($info){
            //获取上传到后台的文件名
            $fileName = $info->getSaveName();
            //获取文件路径
            $filePath = '/uploads/License'.DIRECTORY_SEPARATOR.$fileName;
        }else{
            $this->error('文件过大或格式不正确导致上传失败-_-!');
        }
        return json(array('code' => 1, 'errmsg' => '上传成功', 'data'=>$filePath));
    }
}