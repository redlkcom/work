<?php

namespace app\admin\controller;

use auth\Auth;

class Index extends Common
{
    /**
     * 首页
     * @return mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //获取菜单
        // if(isset($this->group_id[0])){
        //     $group_id = $this->group_id[0];
        // }else{
        //     $group_id = $this->group_id;
        // }
        if(!$this->uid){
            $this->error('未登陆','/admin/index.html');
        }
        $group_id = $this->group_id;
        $menuList = (new Auth($this->uid, $group_id))->getMenuList();
        $this->assign('menuList', $menuList);
        return $this->fetch();
    }

    /**
     * layui 首页
     * @return mixed
     * @author 原点 <467490186@qq.com>
     */
    public function home()
    {
        return $this->fetch();
    }

}
