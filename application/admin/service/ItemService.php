<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/9/7
 * Time: 10:00
 */

namespace app\admin\service;

use app\admin\model\Item;
use think\facade\Request;
use app\admin\traits\Result;
use app\admin\model\Tasks;

class ItemService
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
        $user = new Item;
        $user->name = $data['name'];
        $res = $user->save();
        if ($res) {
            $msg = Result::success('添加成功', url('/admin/itemList'));
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
        if($data['has_tasks'] == 1){
            Tasks::where("item_id = " . $data['id'])->delete();
        }
        $customer_id_arr = explode(",",$data['customer_id']);
        $totals = count($customer_id_arr);
        foreach($customer_id_arr as $k => $v){
            $tasks_data['pro_id'] = $data['pro_id'];
            $tasks_data['item_id'] = $data['id'];
            $tasks_data['cus_id'] = $v;
            $tasks_data['company_id'] = $data['company_id'];
            $tasks_data['name'] = $data['name'];
            $tasks_data['item_date'] = $data['item_date'];
            $tasks_data['performance_fee'] = round(($data['item_fee'] / $totals),2);
            $tasks_data['add_time'] = date("Y-m-d H:i:s",time());
            $res = Tasks::insert($tasks_data);
        }
        if ($res) {
            Item::update(["has_tasks" => 1],["id" => $data['id']]);
            $msg = Result::success('分配成功', url('/admin/itemList'));
        } else {
            $msg = Result::error('分配失败');
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
    public static function sendSalary($data)
    {
        $userdata = [
            'real_fee' => $data['real_fee'],
            'status' => "2",
        ];
        $res = Item::update($userdata, ['id' => $data['id']]);
        if ($res) {
            $msg = Result::success('编辑成功', url('/admin/itemList'));
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
        $res = Item::destroy($id);
        if ($res) {
            $msg = Result::success('删除成功');
        } else {
            $msg = Result::error('删除失败');
        }
        return $msg;
    }

}