<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/9/7
 * Time: 10:00
 */

namespace app\admin\service;

use app\admin\model\Project;
use think\facade\Request;
use app\admin\traits\Result;
use think\Db;

class ProjectService
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
        // $user = new Project;
        // $user->name = $data['name'];
        // $user->company_id = $data['company_id'];
        // $user->start = $data['start'];
        // $user->end = $data['end'];
        // $user->total_fee = $data['total_fee'];
        // $user->description = $data['description'];
        // $res = $user->save();
        $pro_data = [
            'name' => $data['name'],
            'company_id' => $data['company_id'],
            'start' => $data['start'],
            'end' => $data['end'],
            'total_fee' => $data['total_fee'],
            'description' => $data['description']
        ];
        $pro_id = Project::insertGetId($pro_data);
        $i = self::getMonthNum($data['end'],$data['start']);
        $j = 0;
        for($j;$j < $i;$j++){
            $item_data['pro_id'] = $pro_id;
            $item_data['name'] = $data['name'] . (intval($j) + 1);
            $item_data['item_date'] = date("Ym", strtotime($data['start'] . "+" . intval($j) . "month"));
            $item_data['item_fee'] = ceil($data['total_fee'] / $i);
            $item_data['add_time'] = date("Y-m-d H:i:s",time());
            Db::table("think_item")->insert($item_data);
        }
        if ($pro_id > 0) {
            $msg = Result::success('添加成功', url('/admin/projectList'));
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
            'company_id' => $data['company_id'],
            'start' => $data['start'],
            'end' => $data['end'],
            'total_fee' => $data['total_fee'],
            'description' => $data['description']
        ];
        $res = Project::update($userdata, ['id' => $data['id']]);
        if ($res) {
            $msg = Result::success('编辑成功', url('/admin_sp/project/index'));
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
        $res = Project::destroy($id);
        if ($res) {
            $msg = Result::success('删除成功');
        } else {
            $msg = Result::error('删除失败');
        }
        return $msg;
    }

    public static function getMonthNum( $date1='2019-03', $date2='2017-11', $tags='-' ){
        $date1 = explode($tags,$date1);
        $date2 = explode($tags,$date2);
        return abs(intval($date1[0]) - intval($date2[0])) * 12 - intval($date2[1]) + abs(intval($date1[1]));
    }
}