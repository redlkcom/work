<?php

namespace app\admin_sp\controller;

use auth\Auth;
use app\admin_sp\model\Company;
use app\admin_sp\model\Customer;

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
        $menuList = (new Auth($this->uid, 3))->getMenuList();
        foreach($menuList as $k => $v){
            //print_r($v);die;
            if($v['id'] == 19){
                foreach($v['child'] as $k3 => $v3){
                    if($v3['id'] == 20){
                        $menuList[$k]['child'][$k3]['name'] = "admin_sp/project/index";
                    }elseif($v3['id'] == 21){
                        $menuList[$k]['child'][$k3]['name'] = "admin_sp/tasks/index";
                    }elseif($v3['id'] == 22){
                        $menuList[$k]['child'][$k3]['name'] = "admin_sp/tasks/report";
                    }elseif($v3['id'] == 23){
                        unset($menuList[$k]['child'][$k3]);
                    }
                }
            }elseif ($v['id'] == 17){
                foreach($v['child'] as $k3 => $v3){
                    if($v3['id'] == 18){
                        $menuList[$k]['child'][$k3]['name'] = "admin_sp/customer/customerList";
                    }
                }
            }
        }
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
        $companyInfo = Company::get(get_user_id_sp());
        $companyInfo['cus_name'] = Customer::where("id = " . $companyInfo['cus_id'])->value("realname");
        $this->assign('info',$companyInfo);
        return $this->fetch();
    }

}