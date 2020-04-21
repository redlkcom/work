<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/16
 * Time: 15:38
 */

namespace app\admin_sp\model;

use think\Model;

class Company extends Model
{
    protected $auto = [];
    protected $insert = ['pic_url'];
    protected $update = [];
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'add_date';
    protected function setPicUrlAttr(){
        $url = Request()->post('pic_url');
        return $url ? $url : '/images/logo.png';
    }
}