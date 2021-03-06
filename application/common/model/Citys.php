<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/16
 * Time: 15:38
 */

namespace app\common\model;

use think\Model;

class Citys extends Model
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