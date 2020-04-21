<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/16
 * Time: 15:38
 */

namespace app\admin\model;

use think\Model;

class Customer extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    /**
     * 搜索器
     * @param $query
     * @param $value
     */
    public function searchNameAttr($query, $value)
    {
        if ($value) {
            $query->where('realname', 'like', '%' . $value . '%');
        }
    }
}