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
use app\admin\service\AuthGroupService;
use app\admin\service\CompanyService;
use app\admin\model\Company as CompanyModel;
use app\admin\model\Tasks;
use think\facade\Request;
use app\admin\traits\Result;
use think\Db;
use Env;
use PHPExcel_IOFactory;

class Company extends Common
{
    /**
     * 企业用户列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function companyList()
    {
        // echo phpinfo();die;
        if ($this->request->isAjax()) {
            
            $data = [
                'key' => $this->request->get('key', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
            ];
            $list = CompanyModel::withSearch(['name'], ['name' => $data['key']])
                ->paginate($data['limit'], false, ['query' => $data]);
            $company_date = [];
             foreach ($list as $key => $val) {
                 $company_date[$key] = $val;
             }
            $this->json($company_date,0,'',['count' => $list->total()]);
        }
        return $this->fetch();
    }
     /**
     * 添加、编辑企业用户
     * @return mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editCompany()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $file = request()->file('') ; 
            $purl='/upload/xy_pic/';  
            $path = UPLOAD_PATH.'xy_pic/';  //print_r(  $data );
            if(isset($file['xy_pic1'])){
                $info = $file['xy_pic1']->move($path);
                if ($info) {///合作协议
                    $data['xy_pic']= $purl.$info->getSaveName(); 

                }
            }

            if ($data['id']) {
                //编辑
                $res = CompanyService::edit($data);
                //return $res;
            } else {
                //添加
                $data = CompanyService::add($data);
               // return $data;
            }
            echo  '<script>parent.location.reload();</script>';
        } else {
            $id = $this->request->get('id',0,'intval');
            
            if ($id) {
                $list = CompanyModel::where('id', '=', $id)->find();
            }else{
                $list = array(
                    "status" => "1"
                );
            }
            $path="../public/Batch-upload/Agreement/$id.pdf";
            if(file_exists($path)){
                $list['xieyi']="../Batch-upload/Agreement/$id.pdf";
            }else{
                $list['xieyi']=1;
            }
            $this->assign(['list'=>$list]);
            
            return $this->fetch();
        }
    }
    /**
     * 服务费查询
     */
    public function searchFee()
    {
        $id = $this->request->get('id',0,'intval');
        if ($id) {
            $company_name = CompanyModel::where("id = " . $id)->value("name");
            $gm_class = CompanyModel::where("id = " . $id)->value("gm_class");
            $gm_tax_rate = $gm_class == 2 ? 0.03 : 0.06;
            $item_date = date("Ym", strtotime('first day of last month')); // 获取上个月
            $total_unconfirm_fee = Tasks::where([["company_id", "=", $id], ["status", "neq", 1], ["item_date", "=", $item_date]])->sum('real_fee'); // 查询上个月未确认的绩效费总额
            $total_item_fee = Tasks::where(['company_id' => $id, 'status' => 1, 'item_date' => $item_date])->sum('real_fee'); // 上月项目服务费总和
            $total_platform_fee = $total_item_fee * 0.05; // 上月平台服务费
            $total_taxes = ($total_item_fee + $total_platform_fee) * $gm_tax_rate; // 税费
            $total_fee = $total_item_fee + $total_platform_fee + $total_taxes; // 上月需支付总额
            $this->assign('id', $id);
            $this->assign('company_name', $company_name);
            $this->assign('item_date', $item_date);
            $this->assign('total_unconfirm_fee', sprintf("%.2f",$total_unconfirm_fee));
            $this->assign('total_item_fee', sprintf("%.2f",$total_item_fee));
            $this->assign('total_platform_fee', sprintf("%.2f",$total_platform_fee));
            $this->assign('total_taxes', sprintf("%.2f",$total_taxes));
            $this->assign('total_fee', sprintf("%.2f",$total_fee));
        }
        return $this->fetch();
    }
    
    public function ajax_searchfee()
    {
        $id = $this->request->get('id',0,'intval');
        $item_date = $this->request->get('date', '', 'trim');
        if ($item_date) {
            $item_date=$item_date;
        }else {
            $item_date = date("Ym", strtotime('first day of last month')); // 获取上个月
        }
        
        if ($id) {
            $company_name = CompanyModel::where("id = " . $id)->value("name");
            $gm_class = CompanyModel::where("id = " . $id)->value("gm_class");
            $gm_tax_rate = $gm_class == 2 ? 0.03 : 0.06;
            
            $total_unconfirm_fee = Tasks::where([["company_id", "=", $id], ["status", "neq", 1], ["item_date", "=", $item_date]])->sum('real_fee'); // 查询上个月未确认的绩效费总额
            $total_item_fee = Tasks::where(['company_id' => $id, 'status' => 1, 'item_date' => $item_date])->sum('real_fee'); // 上月项目服务费总和
            $total_platform_fee = $total_item_fee * 0.05; // 上月平台服务费
            $total_taxes = ($total_item_fee + $total_platform_fee) * $gm_tax_rate; // 税费
            $total_fee = $total_item_fee + $total_platform_fee + $total_taxes; // 上月需支付总额

            $res = array(
                'id' => $id, 
                'company_name' => $company_name, 
                'item_date' => $item_date, 
                'total_unconfirm_fee' => sprintf("%.2f",$total_unconfirm_fee), 
                'total_item_fee' => sprintf("%.2f",$total_item_fee), 
                'total_platform_fee' => sprintf("%.2f",$total_platform_fee), 
                'total_taxes' => sprintf("%.2f",$total_taxes), 
                'total_fee' => sprintf("%.2f",$total_fee), 
            );
        }
        return json_encode($res);
    }
    function test(){
       $fnapilogic= new \app\common\logic\fnapilogic;

       $fnapilogic->getcity(false);
    }
    /**
     * 确认服务费
     */
    public function confirmTotalFee()
    {
        $id = $this->request->post('id',0,'intval');
        $day = date("d",time()); // 获取当天日期
        if($day > 5){ // 每月5日之后才可执行入账操作
            $item_date = date("Ym", strtotime('first day of last month')); // 获取上个月
            $is_pay = Db::table("think_company_log")->where(["company_id" => $id, "item_date" => $item_date])->count("id");
            if($is_pay > 0){
                $msg = Result::error('上月服务费已结算，请勿反复结算', null, ['token' => Request::token()]);
                return json($msg);
            }
            // 上月项目服务费明细
            $tasks_list = Db::table("think_tasks")
                        ->alias("a")
                        ->join("think_project b","b.id = a.pro_id")
                        ->join("think_customer c", "c.id = a.cus_id")
                        ->join("think_company d", "d.id = a.company_id")
                        ->where(['a.company_id' => $id, 'a.status' => 1, 'a.item_date' => $item_date])
                        ->field('a.id,a.cus_id,a.real_fee,a.item_date,b.name,c.social_fee,c.fund_fee,c.is_substitute,d.tax_class')
                        ->select();
            $customer_arr = [];
            //print_r($tasks_list);die;
            foreach($tasks_list as $k  => $v){
                $customer_info = Db::table("think_customer")->where("id = " . $v['cus_id'])->field("social_fee,fund_fee,is_substitute,circle_regist,circle_close,money")->find();
                // 添加C端绩效费收入记录
                $data['user_id'] = $v['cus_id'];
                $data['tasks_id'] = $v['id'];
                $data['salary'] = $v['real_fee'];
                $data['salary_month'] = $v['item_date'];
                $data['add_time'] = date("Y-m-d H:i:s");
                Db::table("think_salary")->insert($data);
                $umoney=$customer_info['money'];
                $umoney+= $v['real_fee'];
                Db::table("think_customer")->where("id = " . $v['cus_id'])->setInc('money', $v['real_fee']);

                // 记录个税支出
                $person_revenue_fee = sprintf("%.2f",($v['real_fee'] * get_person_revenue($v['tax_class']) / 100));
                $data1 = array(
                    'money' => $person_revenue_fee,
                    'use_type' => "项目《" . $v['name'] . "》" . $v['item_date'] . "月个人所得税",
                    'user_id' => $v['cus_id']
                );
                Db::table("think_payout")->insert($data1); // 插入支出记录表中
                $umoney-= $person_revenue_fee;
                Db::table("think_customer")->where("id = " . $v['cus_id'])->setDec('money', $person_revenue_fee);
 // echo  "  v['cus_id']". $v['cus_id'];
                // 判断该创客是否在别的任务中缴纳过每月只收一次的费用（社保、公积金、代缴服务费）
                if($v['is_substitute'] == 1 && !in_array($v['cus_id'],$customer_arr) &&($customer_info['circle_regist']==1&&$customer_info['circle_close']==0)){

                    ////$v['cus_id']  查询账单有没有 记录 
                    $billarrs=db('bill')->field('id,totalmoney')->where(['numbers'=>$v['cus_id'],'if_kou'=>0 ])->find();////查询 
                    $total_substitute_fee =$billarrs['totalmoney'];//print_r($billarrs); 
                    if($total_substitute_fee>0&&$total_substitute_fee<=$umoney&&$billarrs['id']){


                       // $total_substitute_fee = $customer_info['social_fee'] + $customer_info['fund_fee'] + 30;
                        $data2 = array(
                            'money' => $total_substitute_fee,
                            'use_type' => $v['item_date'] . "月社保代缴费用",
                            'user_id' => $v['cus_id']
                        );
                        Db::table("think_payout")->insert($data2); // 插入支出记录表中
                        // $data3 = array(
                        //     'money' => $customer_info['fund_fee'],
                        //     'use_type' => $v['item_date'] . "月公积金代缴费用",
                        //     'user_id' => $v['cus_id']
                        // );
                        // Db::table("think_payout")->insert($data3); // 插入支出记录表中
                        // $data4 = array(
                        //     'money' => "30",
                        //     'use_type' => $v['item_date'] . "创客平台服务费",
                        //     'user_id' => $v['cus_id']
                        // );
                        // Db::table("think_payout")->insert($data4); // 插入支出记录表中
                        Db::table("think_customer")->where("id = " . $v['cus_id'])->setDec('money', $total_substitute_fee);
                        db('bill')->where('id',$billarrs['id'])->update(['if_kou'=>1]);
                    }else{
                        ///异常账单is_error
                        db('bill')->where('id',$billarrs['id'])->update(['is_error'=>1,'if_kou'=>1]);
                    }
                }
                $customer_arr[] = $v['cus_id'];
            }
            ///$customerarrs=db('customer')->where('money','>',0)->where(['is_substitute'=>1,'deleted'=>0 ])->select();////查询 每月创客交社保
            Db::table("think_company_log")->insert(["company_id" => $id, "item_date" => $item_date]);
            $msg = Result::success('扣款成功', url('/admin/companylist'));
            //$customer_info = Db::table("think_customer")->where("id = " . $v['id'])->field("social_fee,fund_fee,is_substitute")->find();
        }else{
            $msg = Result::error('未到系统结算日期，请在每月5日之后再进行操作', null, ['token' => Request::token()]);
        }
        return json($msg);
    }

    /**
     * 删除用户
     * @author 原点 <467490186@qq.com>
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $res = CompanyService::delete($id);
            return $res;
        } else {
            $this->error('参数错误');
        }
    }

    /**
     * 是否入账查询
     */
    public function transferInfo()
    {
        if(1 == 1){
            return Result::success('已入账');
        }else{
            return Result::error('未入账');
        }
    }
    /**
     * 浦发银行转账明细查询接口
     */
    public function getTransferInfo()
    {
       /* $xml = '<?xml version="1.0" encoding="gb2312"?>';
        $xml .= '<packet>';
        $xml .= '<head>';
        127.0.0.1:4437    // 请求签名
        127.0.0.1:5777    // 请求接口
        
        */
        $sign_url = 'http://127.0.0.1:4437'; //请求签名
        $transfer_url = 'http://127.0.0.1:5777'; //请求接口
        $packet_id = date("YmdHis") . rand(111111,999999);
        $bdate = date("Y-m-01");
        $edate = date('Y-m-d', strtotime(date('Y-m-01') . ' +1 month -1 day'));
        $sign_xml = "<body>
                        <lists name='acctList'>
                            <list>
                                <acctNo>95200078801300000003</acctNo>
                                <beginDate>". $bdate ."</beginDate>
                                <endDate>". $edate ."</endDate>
                                <queryNumber>20</queryNumber>
                                <beginNumber>1</beginNumber>
                                <subAccount>6225160293976253</subAccount>
                                <subAcctName>浦发1339591801</subAcctName>
                            </list>
                        </lists>
                    </body>";
        $length = strlen($sign_xml) + 6;
        $signature = $this->http_xml($sign_url,$sign_xml,$length);
        //header("Content-Type: text/html; charset=utf-8");
        echo "我是谁" . $signature;die;
        //echo iconv("GB2312","UTF-8",$signature);die;
        $xml = "<?xml version='1.0' encoding='gb2312'?>
                <packet>
                    <head>
                        <transCode>8924</transCode>
                        <signFlag>0</signFlag>
                        <masterID>2000040752</masterID>
                        <packetID>" . $packet_id . "</packetID>
                        <timeStamp>" . time() . "</timeStamp>
                    </head>
                    <body>" . $signature . "</body>
                </packet>";
        $length2 = strlen($xml) + 6;
        $res = $this->http_xml($transfer_url,$xml,$length2);
        echo "<pre>";print_r($res);
    }

    /**
     * http请求方法
    */
    public function http_xml($url,$data,$length)
    {
        $ch = curl_init();  // 初始一个curl会话
        $timeout = 30;  // php运行超时时间，单位秒
        curl_setopt($ch, CURLOPT_URL, $url);    // 设置url
        curl_setopt($ch, CURLOPT_POST, 1);  // post 请求
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:INFOSEC_SIGN/1.0;"));    // 一定要定义content-type为xml，要不然默认是text/html！
        //curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:text/xml;"));    // 一定要定义content-type为xml，要不然默认是text/html！
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//post提交的数据包
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // PHP脚本在成功连接服务器前等待多久，单位秒
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);   // 抓取URL并把它传递给浏览器
        // 是否报错
        if(curl_errno($ch))
        {
            print curl_error($ch);
        }
        curl_close($ch);    // //关闭cURL资源，并且释放系统资源
            
        return $result;
    }

    public function importExcel()
    {
        return $this->fetch();
    }
    public function uploadExcel()
    {
        header("content-type:text/html;charset=utf-8");
        //上传excel文件
        $file = request()->file('file');
        //将文件保存到public/uploads目录下面
        $info = $file->validate(['size'=>1048576,'ext'=>'xls,xlsx'])->move( './uploads');
        if($info){
            //获取上传到后台的文件名
            $fileName = $info->getSaveName();
            //获取文件路径
            $filePath = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$fileName;
            //获取文件后缀
            $suffix = $info->getExtension();
            // 引入核心文件
            require'../extend/PHPExcel/PHPExcel.php'; 
            //判断哪种类型
            if($suffix=="xlsx"){
                $reader = PHPExcel_IOFactory::createReader('Excel2007');
            }else{
                $reader = PHPExcel_IOFactory::createReader('Excel5'); 
            }
        }else{
            $this->error('文件过大或格式不正确导致上传失败-_-!');
        }
        //载入excel文件
        $excel = $reader->load("$filePath",$encode = 'utf-8');
        //读取第一张表
        $sheet = $excel->getSheet(0);
        //获取总行数
        $row_num = $sheet->getHighestRow();
        //获取总列数
        $col_num = $sheet->getHighestColumn();
        $data = []; //数组形式获取表格数据
        for ($i=2; $i <=$row_num; $i++) {
            $data['sys_id'] = CompanyService::systemCode();
            $data['user_name'] = $sheet->getCell("A".$i)->getValue();
            $data['name'] = $sheet->getCell("B".$i)->getValue();
            $data['password'] = $this->defaultPwd();
            $data['social_code'] = $sheet->getCell("C".$i)->getValue();
            $data['reg_address'] = $sheet->getCell("D".$i)->getValue();
            $data['ope_address'] = $sheet->getCell("E".$i)->getValue();
            $data['bank_name'] = $sheet->getCell("F".$i)->getValue();
            $data['bank_account'] = $sheet->getCell("G".$i)->getValue();
            $data['contact_name'] = $sheet->getCell("H".$i)->getValue();
            $data['mobile'] = $sheet->getCell("I".$i)->getValue();
            $data['reg_date'] = $sheet->getCell("J".$i)->getValue();
            $data['end_date'] = $sheet->getCell("K".$i)->getValue();
            $data['add_date'] = date("Y-m-d H:i:s");
            //将数据保存到数据库
            $res = CompanyModel::insert($data);
        }
        return json(array('state' => 1, 'errmsg' => '导入成功', 'data'=>$data));
    }
}