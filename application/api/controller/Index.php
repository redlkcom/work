<?php

namespace app\api\controller;

use auth\Auth;
use think\Db;
use think\Controller;

class Index extends Controller
{
    public function test111()
    {

        $var=sprintf("%08d", 2045);
        echo $var;//结果为0002 
        die; 
        $filename = 'file.txt';

        $word = "";  //双引号会换行 单引号不换行
        foreach($_POST as $k => $v){
            $word .= $k . ":" . $v . "\r\n";
        }
        $word .= time() . "\r\n";
        file_put_contents($filename, $word,FILE_APPEND|LOCK_EX);
        echo "111";
    }
    /**
     * 首页
     */
    public function index()
    {
        $user_id = input("get.user_id");
        $status = input("get.status");
        $start = input("get.startDate");
        $end = input("get.endDate");
        if($user_id == ""){
            $result = array(
                'code' => '400',
                'message' => '参数错误',
                'data' => '0',
            );
            return json_encode($result);die;
        }else{
            $where = "cus_id = " . $user_id;
            if($status != ""){
                $where .= " and status = " . $status;
            }
            if($start != "" && $end != ""){
                $startDate = date("Ym", strtotime($start));
                $endDate = date("Ym", strtotime($end));
                $where .= " and item_date between " . $startDate . " and " . $endDate;
            }
            //echo $where;die;
            $list = Db::table('think_tasks')->where($where)->select();
        }
        $result = array(
            'code' => '200',
            'message' => 'success',
            'data' => $list,
        );
        return json_encode($result);die;
    }
    /**
     * 修改状态
     */
    public function update()
    {
        $item_id = input("get.item_id");
        $status = input("get.status");
        if($item_id == "" || $status == ""){
            $result = array(
                'code' => '400',
                'message' => '参数错误',
                'data' => '0',
            );
            return json_encode($result);die;
        }else{
            
        }
    }
    /**
     * 薪资明细
     */
    public function salaryList()
    {
        $user_id = input("get.user_id");
        $start = input("get.startDate");
        $end_s = input("get.endDate");
        $end = date("Y-m-d",strtotime("+1 day", strtotime($end_s)));
        if(!is_numeric($user_id)){
            $result = array(
                'code' => '400',
                'message' => '参数错误',
                'data' => '0',
            );
            return json_encode($result);die;
        }else{
            $where = "a.user_id = " . $user_id;
            if($start != "" && $end != ""){
                $where .= " and a.add_time between '" . $start . "' and '" . $end . "'";
            }
            $list = Db::table('think_salary')
                ->alias('a')
                ->join('think_tasks b ','b.id= a.tasks_id')
                ->where($where)
                ->order('a.salary_month desc')
                ->field('a.*,b.name as tasks_name')
                ->select();
            foreach($list as $k => $v){
                $list[$k]['add_time'] = date("Y-m-d",strtotime($v['add_time']));
            }
        }
        $result = array(
            'code' => '200',
            'message' => 'success',
            'data' => $list,
        );
        return json_encode($result);die;
    }
    /**
     * 支出明细
     */
    public function payoutList()
    {
        $user_id = input("get.user_id");
        $start = input("get.startDate");
        $end_s = input("get.endDate");
        $end = date("Y-m-d",strtotime("+1 day", strtotime($end_s)));
        if(!is_numeric($user_id)){
            $result = array(
                'code' => '400',
                'message' => '参数错误',
                'data' => '0',
            );
            return json_encode($result);die;
        }else{
            $where = "user_id = " . $user_id;
            if($start != "" && $end != ""){
                $where .= " and add_time between '" . $start . "' and '" . $end . "'";
            }
            $list = Db::table('think_payout')->where($where)->order('add_time desc')->select();
        }
        $result = array(
            'code' => '200',
            'message' => 'success',
            'data' => $list,
        );
        return json_encode($result);die;
    }
    /**
     * 提现明细
     */
    public function accountList()
    {
        $user_id = input("get.user_id");
        $start = input("get.startDate");
        $end = input("get.endDate");
        if(!is_numeric($user_id)){
            $result = array(
                'code' => '400',
                'message' => '参数错误',
                'data' => '0',
            );
            return json_encode($result);die;
        }else{
            $where = "cid = " . $user_id;
            if($start != "" && $end != ""){
                $where .= " and timeStamp between " . $start . " and " . $end;
            }
            $list = Db::table('think_account')->where($where)->order('id desc')->select();
        }
        $result = array(
            'code' => '200',
            'message' => 'success',
            'data' => $list,
        );
        return json_encode($result);die;
    }
    public function accountApply()
    {
        $data['cid'] = input("get.user_id");
        $data['amount'] = input("get.money");

        //$data['add_time'] = date("Y-m-d H:i:s");
        Db::startTrans();
        $cuser=db('customer')->field('id_number,bank_name,bank_card,realname,money')->find($data['cid']);//今天分享第几个点击人
        $data['payeeBankName']=$cuser['bank_name'];
        $data['payeeAcctNo']=$cuser['bank_card'];//收款人账号
        $data['payeeName']=$cuser['realname'];
        $data['usermoney']=$cuser['money'];

        ///$data['elecChequeNo']=date("Ymd").createCheckCode(6,0);//电子凭证号
        $Common=new \app\common\model\Common();
        $data['elecChequeNo']=$Common->getpay_code( 'account', '' , 'elecChequeNo', ['sntype'=>'account','snnum'=>6]);

        $bankpay=config('bankpay'); 
        $data['acctNo']=$bankpay['acctNo'];//电子凭证号
        $data['acctName']=$bankpay['acctName'];//电子凭证号 

        
        $res = Db::table("think_account")->insertGetId($data);
        $r3=db('customer')->where('id', $data['cid'])->setDec('money', $data['amount']);
        if($res > 0&&$r3){
             
            Db::commit(); 
            $result = array(
                'code' => '200',
                'message' => '提现成功',
                'data' => $res,
            );
           
            
        }else{
            Db::rollback();
            $result = array(
                'code' => '400',
                'message' => '提现失败',
                'data' => $res,
            );
        }
        return json_encode($result);die;
    }
    public function confirmTasksStatus()
    {
        $id = input("get.id");
        $status = input("get.status");
        // if($status == 3){
        //     $info = Db::table("think_tasks")->where("id = " . $id)->field("pro_id,cus_id")->find();
        //     $res = Db::table("think_tasks")->where("pro_id = " . $info['pro_id'] . " and cus_id = " . $info['cus_id'])->update(array("status" => $status));
        // }else{
        //     $res = Db::table("think_tasks")->where("id = " . $id)->update(array("status" => $status));
        // }
        $res = Db::table("think_tasks")->where("id = " . $id)->update(array("status" => $status));
        if($res){
            // if($status == 1){
            //     $tasks_info = Db::table("think_tasks")->where("id = " . $id)->field("cus_id,real_fee,item_date")->find();
            //     $data['user_id'] = $tasks_info['cus_id'];
            //     $data['tasks_id'] = $id;
            //     $data['salary'] = $tasks_info['real_fee'];
            //     $data['salary_month'] = $tasks_info['item_date'];
            //     $data['add_time'] = date("Y-m-d H:i:s");
            //     Db::table("think_salary")->insert($data);
            //     Db::table("think_customer")->where("id = " . $tasks_info['cus_id'])->setInc('money', $tasks_info['real_fee']);
            // }
            $result = array(
                'code' => '200',
                'message' => '操作成功',
                'data' => $res,
            );
        }else{
            $result = array(
                'code' => '400',
                'message' => '操作失败，请稍后重试',
                'data' => $res,
            );
        }
        return json_encode($result);
    }
}
