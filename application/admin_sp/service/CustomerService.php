<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/9/7
 * Time: 10:00
 */

namespace app\admin_sp\service;

use app\admin_sp\model\Customer;
use think\facade\Request;
use app\admin_sp\traits\Result;

class CustomerService
{
    use Result;

    /**
     * 添加用户
     * @param $data
     * @return array
     * @author 原点 <467490186@qq.com>
     * @throws \Exception
     */
    public static function add($data)
    {
        $user = new Customer;
        $user->username = $data['username'];
        $user->itemname = $data['itemname'];
        $user->realname = $data['realname'];
        $user->sex = $data['sex'];
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $res = $user->save();
        if ($res) {
            $msg = Result::success('添加成功', url('/admin/customerList'));
        } else {
            $msg = Result::error('添加失败', null, ['token' => Request::token()]);
        }
        return $msg;
    }

    /**
     * 编辑用户
     * @param $data
     * @return array|string
     * @author 原点 <467490186@qq.com>
     * @throws \Exception
     */
    public static function edit($data)
    {
        $userdata = [
            'name' => $data['name'],
            'status' => $data['status'],
        ];
        $res = Customer::update($userdata, ['id' => $data['id']]);
        if ($res) {
            $msg = Result::success('编辑成功', url('/admin/customerList'));
        } else {
            $msg = Result::error('编辑失败');
        }
        return $msg;
    }

    /**
     * 删除用户
     * @param $uid 用户id
     * @return array|string
     * @author 原点 <467490186@qq.com>
     * @throws \Exception
     */
    public static function delete($id)
    {
        if (!$id) {
            return Result::error('参数错误');
        }
        $res = Customer::destroy($id);
        if ($res) {
            $msg = Result::success('删除成功');
        } else {
            $msg = Result::error('删除失败');
        }
        return $msg;
    }

}