<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 15:05
 */

namespace app\admin_sp\controller;

use app\admin_sp\service\CustomerService;
use app\admin_sp\service\CompanyService;
use app\admin_sp\model\Customer as CustomerModel;
use think\db;
use app\admin\service\UserService;
use app\admin\model\User as UserModel;
class Company extends Common
{
    /**
     * 修改密码
     * @return array|mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\Exception\DbException
     */
    public function editPassword()
    {
           if (request()->isPost()) {
            $data = input();
            $id = $_SESSION['think']['user_auth_sp']['id'];
            $pwd['password']=md5($data['password']);
            $res = db('company')->where('id',$id)->update($pwd);
            
            if ($res==1) {
                $msg= [
                    'code' => 1,
                    'msg' => '修改成功',
                    'url'=>'logout'
                ];
                return $msg;
            } else {
                 $msg= [
                    'code' => '',
                    'msg' => '修改失败',
                    'url'=>'editPassword'
                ];
                return $msg;
            }
            
      } 
       return $this->fetch();
         
    }
    public function ajax(){
        if (request()->isPost()) {
            $id = $_SESSION['think']['user_auth_sp']['id'];
            $password=$_POST['password'];
            $where['id'] = $id;
            $where['password'] = md5($password);
            $res=db('company')->where($where)->find();
            if (!$res) {
                return 1;
            }
        }
    }

    public function logout(){
        session('user_auth_sp', null);
        session('user_auth_sign_sp', null);
        $this->redirect('Login/login');
    }
}