<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/9/7
 * Time: 10:00
 */

namespace app\admin_sp\service;

use app\admin_sp\model\Company;
use think\facade\Request;
use app\admin_sp\traits\Result;

class CompanyService
{
    use Result;
    /**
     * 验证登录
     * @param $data  待验证数据
     * @return array|string
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function login($data)
    {
        $validate = validate('User');
        if (!$validate->check($data)) {
            return Result::error($validate->getError());
        }
        $list = Company::where(['user_name' => $data['user']])->find();
        if (empty($list)) {
            return Result::error('账号不存在');
        }
        if(empty($list['sys_id'])){
            return Result::success('请先完善信息', url('/admin_sp/login/completion_info?id='.$list['id']));
        }
        if ($list['deleted'] == 1) {
            $msg = Result::error('账号禁用');
        } elseif (md5($data['password']) != $list['password']) {
            $msg = Result::error('密码错误');
        } else {
            self::autoSession($list['id']);
            $msg = Result::success('登录成功', url('/admin_sp/index/index'));
        }
        return $msg;
    }

    /**
     * 用户注册
     * @param $data  待验证数据
     * @return array|string
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function register($data)
    {
        $validate = validate('Company');
        if (!$validate->scene('add')->check($data)) {
            return Result::error($validate->getError());
        }
        $company = new Company();
        $company->user_name = $data['user_name'];
        $company->password = md5($data['password']);
        $res = $company->save();
        if ($res) {
            $msg = Result::success('注册成功，正在前往完善资料', url('/admin_sp/login/completion_info?id='.$company->id));
        } else {
            $msg = Result::error('注册失败');
        }
        return $msg;
    }


    /**
     * 用户注册
     * @param $data  待验证数据
     * @return array|string
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function save_info($data)
    {
        $validate = validate('Company');
        if (!$validate->scene('edit')->check($data)) {
            return Result::error($validate->getError());
        }
        $company = new Company();
        $data['sys_id'] =date("Ym") .$data['eco_class'].$data['tax_class']. str_pad ($data['id']+rand(0,3),6,'0', STR_PAD_LEFT).$data['post_code'];
        $cus_info = explode("-",$data['customerinfo']);
        $data['cus_id'] = $cus_info[0];
        $data['cus_mobile'] = $cus_info[1];
        unset($data['customerInfo']);
        $res = $company->isUpdate(true)->save($data);
        if ($res) {
            self::autoSession($data['id']);
            $msg = Result::success('信息保存成功，正在为您跳转到首页', url('/admin_sp/index/index'));
        } else {
            $msg = Result::error('信息保存失败');
        }
        return $msg;
    }

    /**
     * 记录session
     * @param $uid 用户id
     * @author 原点 <467490186@qq.com>
     * @throws \think\Exception\DbException
     */
    private static function autoSession($uid)
    {
        //获取用户信息
        $user = Company::get($uid);
        /* 记录登录SESSION */
        $data = [
            'id' => $uid,
            'user' => $user['user_name'],
            'name' => $user['name'],
            'pic_url' => $user['pic_url'],
            'group_id' => 3,
            'login_count' => ['inc', 1],
            'last_login_ip' => request()->ip(),
            'last_login_time' => time(),
        ];
        //设置session
        session('user_auth_sp', $data);
        //设置session签名
        session('user_auth_sign_sp', sign($data));
    }

    /**
     * 修改密码
     * @param $uid     用户id
     * @param $oldpsd  原密码
     * @param $newpsd  新密码
     * @return array
     * @author 原点 <467490186@qq.com>
     * @throws \think\Exception\DbException
     */
    public static function editPassword($uid, $oldpsd, $newpsd)
    {
        $list = Company::where(['id' => $uid])->find();
        if (md5($oldpsd) != $list['password']) {
            $msg = Result::error('原密码错误');
            return $msg;
        }
        $list->password = md5($newpsd);
        $list->updatapassword = 1;
        if ($list->save()) {
            //清除当前登录信息
            session('user_auth_sp', null);
            session('user_auth_sign_sp', null);
            $msg = Result::success('重置成功,请重新登录', url('/admin_sp/login/login'));
        } else {
            $msg = Result::error('修改失败');
        }
        return $msg;
    }

}