<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/9/7
 * Time: 10:00
 */

namespace app\admin_sp\service;

use app\admin_sp\model\Item;
use think\facade\Request;
use app\admin_sp\traits\Result;
use app\admin_sp\model\Tasks;
use think\Db;

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
            $msg = Result::success('添加成功', url('/admin/item/index'));
        } else {
            $msg = Result::error('添加失败', null, ['token' => Request::token()]);
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

    /**
     * 编辑项目明细
     * @param $data
     * @return array|string
     * @author dustin
     * @throws \Exception
     */
    public static function edit($data)
    {
        // 查询相关信息  项目明细金额是否会超过项目总金额
        $row = Db::view('item','id,pro_id,item_fee')
                ->view('project','total_fee', 'project.id = item.pro_id')
                ->where('item.pro_id',$data['pro_id'])
                ->where("item.id","<>",$data['id'])
                ->select();
        $item_fee = (float)$data['item_fee'];

        if($row){
            foreach ($row as $key => $value) {
                $item_fee += (float)$value['item_fee'];
                if($item_fee > $value['total_fee']){
                    return Result::error('项目明细金额超过项目总金额，不允许修改');
                }
            }
            $userdata = [
                'item_fee' => (float)$data['item_fee']
            ];
            $res = Item::update($userdata, ['id' => $data['id']]);
            if ($res) {
                $msg = Result::success('编辑成功', url('/admin_sp/item/index'));
            } else {
                $msg = Result::error('编辑失败');
            }
            return $msg;
        }else{
            return Result::error('未查询到相关项目信息');
        }
    }

}