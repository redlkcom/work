<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 15:05
 */

namespace app\admin\controller;


use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\admin\model\AuthRule;
use app\admin\service\UserService;
use app\common\logic\pfbanklogic;
use app\admin\model\User as UserModel;

class Bank extends Common
{
    /**
     * 支付管理
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function paylist()
    {   $pfbanklogic= new pfbanklogic;
        
        return $this->fetch();
    }

    /**
     * 日提现汇总表
     * @return mixed
     * @author 原点 <467490186@qq.com>
     * 
     */
    public function daycash()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->get();
            if ($data['uid']) {
                
            } 
            if ($data['starttime']) {
                $datetime=explode('~', $data['starttime']);
                $map[]=['timeStamp','between time', [$datetime[0] , $datetime[1].' 23:59:59' ]];
            }
            $list=db('account')->where($map)->paginate($limit, false, ['query' => ['key' => $key], 'limit' => $limit]);
            if($list){
                foreach ($list as $key => $value) {
                    $cids[]=$value['cid'];
                }
                if($cids){
                    $Common=new \app\common\model\Common();
                    $customerlist=$Common->selectindex(db('customer')->where('id','in',$cids)->select());
                    foreach ($list as $key => $value) {
                        $cids[]=$value['cid'];
                    }
                }
            }
            $this->json($list->items(),0,'',['count' => $list->total()]);
        } else {
           
            return $this->fetch();
        }
    }

    /**
     * 8801单笔支付
     * @return array 
     * @author 原点 <467490186@qq.com>
     */
    public function bankpay8801()
    {
        if ($this->request->isAjax()) {

            $pfbanklogic= new \app\common\logic\pfbanklogic;
            $data = $this->request->get();
            if ($data['uid']) {
                
            }  
            $limit = $this->request->get('limit', 10, 'intval');
            if ($data['starttime']) {
                $datetime=explode('~', $data['starttime']);
                $map[]=['timeStamp','between time', [$datetime[0] , $datetime[1].' 23:59:59' ]];
            }
            $list=db('account')->where('transStatus','>','0')->where('acceptNo','<>','')->where($map)->group('acceptNo')->paginate($limit, false, ['query' => ['key' => $key], 'limit' => $limit]);
            if($list){
                $datalist=[];
                foreach ($list as $key => $value) {
                    $cids[]=$value['cid']; 
                    if($value['acceptNo']){
                        $datas1['handleSeqNo']=$value['acceptNo'];
                        $rdatas=$pfbanklogic->payAQ54($datas1);
                        if(is_array($rdatas)){
                            $datalist=array_merge($datalist,$rdatas);
                        }
                    }
                }
                if($cids){
                    // $Common=new \app\common\model\Common();
                    // $customerlist=$Common->selectindex(db('customer')->where('id','in',$cids)->select());
                    // foreach ($list as $key => $value) {
                    //     $cids[]=$value['cid'];
                    // }
                }
            }//print_r($datalist);
            $this->json($datalist,0,'',['count' => $list->total()]);
        } else {
           
            return $this->fetch();
        }
    }

    /**
     * 8809支付 交易撤销
     * @author 原点 <467490186@qq.com>
     */
    public function bankpay8809()
    {
        if ($this->request->isAjax()) {

            $pfbanklogic= new \app\common\logic\pfbanklogic;
            $data = $this->request->post();
            if ($data['uid']) {
                
            }  
            $limit = $this->request->get('limit', 10, 'intval');
            if ($data['starttime']) {
                $datetime=explode('~', $data['starttime']);
                $map['timeStamp']=['BETWEEN',[$datetime[0],trim($datetime[1]).' 23:59:59']];
            }
            $list=db('account')->where('transStatus','>','0')->where('acceptNo','<>','')->where($map)->paginate($limit, false, ['query' => ['key' => $key], 'limit' => $limit]);
            if($list){
                $datalist=[];
                foreach ($list as $key => $value) {
                    $cids[]=$value['cid']; 
                    if($value['acceptNo']){
                        $datas1['handleSeqNo']=$value['acceptNo'];
                        $rdatas=$pfbanklogic->payAQ54($datas1);
                        if(is_array($rdatas)){
                            $datalist=array_merge($datalist,$rdatas);
                        }
                    }
                }
                if($cids){
                    // $Common=new \app\common\model\Common();
                    // $customerlist=$Common->selectindex(db('customer')->where('id','in',$cids)->select());
                    // foreach ($list as $key => $value) {
                    //     $cids[]=$value['cid'];
                    // }
                }
            }//print_r($datalist);
            $this->json($datalist,0,'',['count' => $list->total()]);
        } else {
           
            return $this->fetch();
        }
    }
    /**
     * 账户查询
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function account()
    {  
        
        return $this->fetch();
    }

    /**
     * 4402账户查询
     * @return mixed
     * @author 原点 <467490186@qq.com>
     * 
     */
    public function account4402()
    {
        
        if ($this->request->isAjax()) {
            $pfbanklogic= new pfbanklogic;
            $list=$pfbanklogic->pay4402(  );//print_r($list);
            $this->json($list['datalist'] ,0,'' );
        } else {
           
            return $this->fetch();
        }
    }
    /**
     * 4403对公活期账户历史余额查询
     * @return mixed
     * @author 原点  
     * 
     */
    public function account4403()
    {
        
        if ($this->request->isAjax()) {
            $pfbanklogic= new pfbanklogic;
            $bankpay=config('bankpay'); 
            $data['acctNo']=$bankpay['acctNo'];//电子凭证号 

            $datetime = input("get.datetime");
            if($datetime){
                $d=explode('~', $datetime);
                $data['beginDate']=str_replace('-', '', $d[0]);
                $data['endDate']=str_replace('-', '',  $d[1]);
            }else{

                $data['beginDate']=date('Ymd');
                $data['endDate']=date('Ymd');
            }
            
            $list=$pfbanklogic->pay4403( $data );//print_r($list);
            $this->json($list['datalist'] ,0,'' );
        } else {
           
            return $this->fetch();
        }
    }


    /**
     * 89244.3账户明细查询
     * @return mixed
     * @author 原点  
     * 
     */
    public function account8924()
    {
        
        if ($this->request->isAjax()) {
            $pfbanklogic= new pfbanklogic;
            $bankpay=config('bankpay'); 
            $data['acctNo']=$bankpay['acctNo'];//电子凭证号 

            $datetime = input("get.datetime");
            if($datetime){
                $d=explode('~', $datetime);
                $data['beginDate']=str_replace('-', '', $d[0]);
                $data['endDate']=str_replace('-', '',  $d[1]);
            }else{

                $data['beginDate']=date('Ymd');
                $data['endDate']=date('Ymd');
            }
            if($page){
                $data['beginNumber']=$page;
            }
            
            $list=$pfbanklogic->pay8924( $data );
            if($list['code']){


                if($list['totalCount']>0){
                    foreach ($list['datalist'] as $key => &$value) {
                        $value['voucherNo']=implode(',',$value['voucherNo']);
                        $value['tranFlag']=$value['tranFlag']==1?'贷':'借';
                    }

                }
            }
            $this->json($list['datalist'] ,0,$list['msg'] ,['count'=>$list['totalCount']]);
        } else {
           
            return $this->fetch();
        }
    }


    /**
     * 用户组管理
     * @return array|mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function groupList()
    {
        if ($this->request->isPost()) {
            $id = $this->request->post('id', 0, 'intval');
            $type = $this->request->post('type', 0, 'intval');
            $title = $this->request->post('title', '', 'trim');
            $status = $this->request->post('status', 0, 'intval');
            $rules = $this->request->post('rules', []);
            switch ($type) {
                case 1://编辑、添加用户组
                    if ($id) {//编辑用户组
                        return AuthGroupService::edit($id, ['title' => $title]);
                    } else {//添加用户组
                        return AuthGroupService::add($title);
                    }
                    break;
                case 2://是否禁用用户组
                    return AuthGroupService::edit($id, ['status' => $status]);
                    break;
                case 3://获取权限列表
                    $list = AuthRule::field('id,pid,title as text')->where(['status'=>1])->select();
                    $data = list_to_tree($list->toArray(), 'id', 'pid', 'children');
                    return $data;
                    break;
                case 4://修改用户组权限
                    if (!$rules) $this->error('参数错误');
                    sort($rules);
                    $rules = implode(',', $rules);
                    $res = AuthGroupService::edit($id, ['rules' => $rules], true);
                    return $res;
                    break;
            }
        } else {
            if ($this->request->isAjax()) {
                $key = $this->request->get('key', '', 'trim');
                $limit = $this->request->get('key', 10, 'intval');
                $map = [];
                empty ($key) || $map[] = ['title', 'like', '%' . $key . '%'];
                $list = AuthGroup::where($map)->paginate($limit, false, ['query' => ['key' => $key], 'limit' => $limit]);
                $this->json($list->items(),0,'',['count' => $list->total()]);
            }
            return $this->fetch();
        }
    }

    /**
     * 修改密码
     * @return array|mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\Exception\DbException
     */
    public function editPassword()
    {
        if ($this->request->isPost()) {
            $data = input();
            $uid = get_user_id();
            $res = UserService::editPassword($uid, $data['oldpassword'], $data['password']);
            return $res;
        } else {
            return $this->fetch();
        }
    }

}