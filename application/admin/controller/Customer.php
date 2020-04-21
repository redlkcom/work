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
use app\admin\service\CustomerService;
use app\admin\model\Customer as CustomerModel;
use think\db;
use Env;
use PHPExcel_IOFactory;
use app\admin\traits\Result;

class Customer extends Common
{
    /**
     * 企业用户列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function customerList()
    {
        if ($this->request->isAjax()) {
            
            $data = [
                'key' => $this->request->get('key', '', 'trim'),
                'regstday' => $this->request->get('regstday', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval')
            ];
            $smodel=CustomerModel::where('realname|id','like','%' . $data['key'] . '%')->order('id','desc');
            if($data['regstday']){
                $smodel->where('add_date',['between',[$data['regstday'],date('Y-m-d',strtotime('1 days',strtotime($data['regstday'])))]]);
            }
            $list = $smodel
                ->paginate($data['limit'], false, ['query' => $data]);
                //print_r($list);die;
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                //$company_name = Db::table('think_company')->where("id = " . $val['company_id'])->value('name');
                //$customer_date[$key]['company_name'] = $company_name;
                if($val['is_name_auth'] == 1){
                    $customer_date[$key]['is_name_auth_zh'] = "已认证";
                }else{
                    $customer_date[$key]['is_name_auth_zh'] = "未认证";
                }
                if($val['circle_regist'] == 0||$val['circle_close'] == 1){
                    $customer_date[$key]['is_circle'] = "否";
                }else{
                    $customer_date[$key]['is_circle'] = "是";
                }
                $customer_data[$key]['performance_fee'] = round($val['performance_fee'], 2);
            }
            $this->json($customer_date,0,'',['count' => $list->total()]);
        }
        return $this->fetch();
    }
     /**
     * 添加、编辑员工用户
     * @return mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editCustomer()
    {
        if ($this->request->isPost()) {
            $Common= new \app\common\model\Common;
            $data = $this->request->post();
            $file = request()->file('') ; 
            $purl='/upload/authrealname/';  
            $path = UPLOAD_PATH.'authrealname/';  
            if(isset($file['files'])){
                $info = $file['files']->move($path);////print_r($info );exit;
                if ($info) {///营业执照
                    $data['license_pic']= $purl.$info->getSaveName();
                    $data['circle_regist']=1;//等待A端上传营业执照后会自动变为是。变为”是“后判断创客可接单

                    $Common->Add_data(['uid'=>$data['id'],'name'=>'A端上传营业执照','type'=>1],'log');//日志

                }
            }
            
            
           // $file1 = request()->file('files') ; 
            if(isset($file['circle_closepic'])){ 
                $info = $file['circle_closepic']->move($path);
                if ($info) {///注销凭证
                    $data['circle_closepic']= $purl.$info->getSaveName();
                    $data['circle_close']=1;//等待A端注销凭证后会自动变为是


                }
            }

            if ($data['id']) {
                //编辑
                $res = CustomerService::edit($data);
                //return $res;
            } else {
                //添加
                $data = CustomerService::add($data);
                //return $data;
            }
            echo  '<script>parent.location.reload();</script>';
            //$this->success('编辑成功', url('/admin/customerList'));
        } else {
            $id = $this->request->get('id',0,'intval');
            if ($id) {
                $list = CustomerModel::where('id', '=', $id)->find();
            }else{
                $list = "";
            }
            $this->assign('list', $list);
            return $this->fetch();
        }
    }
     /**
     * 查看创客社保信息
     * @return mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editInsurance()
    {
        $id = $this->request->get('id',0,'intval');
        if ($id) {
            $list = Db::table('think_insurance')->where('customer_id', '=', $id)->find();
        }else{
            $list = "";
        }
        $this->assign('list', $list);
        return $this->fetch();
    }
     /**
     * 添加、修改创客社保金额
     * @return mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editCustomerFee()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['id']) {
                $res = CustomerService::editFee($data);
                //echo "<pre>";print_r($res);die;
                return $res;
            } else {
                $this->error('参数错误');
            }
        } else {
            $id = $this->request->get('id',0,'intval');
            if ($id) {
                $list = CustomerModel::where('id', '=', $id)->find();
            }else{
                $list = "";
            }
            $this->assign('list', $list);
            return $this->fetch();
        }
    }

    /**
     * 删除用户
     * @author bing点 <46274933301846@qq.com>
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $res = CustomerService::delete($id);
            return $res;
        } else {
            $this->error('参数错误');
        }
    }

    /**
     * 
     */
    public function sendSalary()
    {
        $id = $this->request->param('id',0,'intval');
        if($id){
            $data['salary_month'] = date("Ym");
            $data['user_id'] = $id;
            $is_send = Db::table("think_salary")->where($data)->value("id");
            $salary = Db::table("think_customer")->where(['id'=>$id])->field("performance_fee,mobile")->find();
            if($is_send > 0){
                $this->error('当月薪资已发放，请勿重复发放！');
            }else{
                $data['salary'] = $salary['performance_fee'];
                $data['add_time'] = date("Y-m-d H:i:s");
                Db::table("think_salary")->insert($data);
                Db::table("think_customer")->where(['id'=>$id])->setInc("money",$salary);
                // 发送通知短信
                $res = sendMsg($salary['mobile'],"SMS_163055009","",$salary['performance_fee']);
                $this->json($res,1,"发放成功！");
            }
        }else{
            $this->error('参数错误');
        }
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
            $data['username'] = $sheet->getCell("A".$i)->getValue();
            $data['realname'] = $sheet->getCell("B".$i)->getValue();
            $data['password'] = $this->defaultPwd();
            $data['sex'] = $sheet->getCell("C".$i)->getValue();
            $data['id_number'] = $sheet->getCell("D".$i)->getValue();
            $data['id_address'] = $sheet->getCell("E".$i)->getValue();
            $data['real_address'] = $sheet->getCell("F".$i)->getValue();
            $data['mobile'] = $sheet->getCell("G".$i)->getValue();
            $data['performance_fee'] = $sheet->getCell("H".$i)->getValue();
            $data['add_date'] = date("Y-m-d H:i:s");
            //将数据保存到数据库
            $res = CustomerModel::insert($data);
        }
        return json(array('state' => 1, 'errmsg' => '导入成功', 'data'=>$data));
    }

    /**
     * 创客社保管理信息
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    function socialinsurance(){
        return $this->fetch();
    }

     /**
     * 城市社保列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function customermanage()
    {   
        $fnapilogic= new \app\common\logic\fnapilogic;
        $citys=Cache('citys' );
        //print_r($citys);exit;
        if ($this->request->isPost()) {
            $citys=$fnapilogic->getcity(false);
            if($citys){
                $Common= new \app\common\model\Common;
           
                db('citys')->where('id','>',0)->delete();
           
                foreach ($citys as $key => $value) {
                    $GetCityPolicy=$fnapilogic->GetCityPolicy($value['id']);
                    $udate['id']=$value['id'];
                    $udate['name']=$value['cityName'];
                    $udate['minsalary']=$GetCityPolicy['socialBaseLow'];
                    $udate['maxsalary']=$GetCityPolicy['socialBaseHigh'];
                    $udate['gmin']=$GetCityPolicy['accumulationBaseLow'];
                    $udate['gmax']=$GetCityPolicy['accumulationBaseHigh'];
                    $udate['accumulationBaseProportion']=implode(',', $GetCityPolicy['accumulationBaseProportion']); 
                    $Common->Add_data($udate,'citys'  );
                }
            } 
            return json(array('code' => 1, 'errmsg' => '导入成功' ));  
            
        }

        if ($this->request->isAjax()) {
            
            $data = [
                'key' => $this->request->get('key', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval')
            ];
            $list = \app\common\model\Citys::paginate($data['limit'], false, ['query' => $data]);
            //print_r($list->toArray());die;
            $company_date = [];
             foreach ($list as $key => $val) {
                 $company_date[$key] = $val;
             }
      
            
            $this->json($company_date,0,'',['count' => $list->total()]);
        }
        return $this->fetch();
    }
     /**
     * 添加、编辑社保供应商 
     * @return mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit_supplier()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['id']) {
                //编辑
                $res = \app\common\model\Supplier::update($data, ['id' => $data['id']]);
                if ($res) {
                    $msg = Result::success('编辑成功', url('/admin/InsuranceList'));
                } else {
                    $msg = Result::error('编辑失败');
                }
                return  ($msg);
            } else {
                //添加
                $res = \app\common\model\Supplier::insert($data);
                if ($res) {
                    $msg = Result::success('添加成功', url('/admin/InsuranceList'));
                } else {
                    $msg = Result::error('添加失败');
                } 
                return  ($msg);
            }
        } else {
            $id = $this->request->get('id',0,'intval');
            if ($id) {
                $list = \app\common\model\Supplier::where('id', '=', $id)->find();
            }else{
                $list = "";
            }
            $this->assign('list', $list);
            return $this->fetch();
        }
    }

    /**
     * 删除社保供应商 
     * @author bing点 <46274933301846@qq.com>
     */
    public function delsupplier()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $res = \app\common\model\Supplier::destroy($id);
            if ($res) {
                $msg = Result::success('删除成功');
            } else {
                $msg = Result::error('删除失败');
            }
            return $msg;
        } else {
            $this->error('参数错误');
        }
    }

    /**
     * 社保供应商管理
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function InsuranceList()
    {   
        $fnapilogic= new \app\common\logic\fnapilogic;
        $citys=Cache('citys' );
        //print_r($citys);exit;
        if ($this->request->isPost()) {
            
            return json(array('code' => 1, 'errmsg' => '导入成功' ));  
            
        }

        if ($this->request->isAjax()) {
            
            $data = [
                'key' => $this->request->get('key', '', 'trim'), 'idVal' => $this->request->get('idVal', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval')
            ];
            $con=[];
            

            if($data['idVal']){
                $data['idVal']=intval(substr($data['idVal'], 3));//var_dump($data['idVal']);
                $con['id']=$data['idVal'];
            }

            $model=\app\common\model\Supplier::where($con)->order('id','desc');

            if($data['key']){
                $model->where(  'name','like','%' . $data['key'] . '%'  ) ;
            }
            $list = $model->paginate($data['limit'], false, ['query' => $data]);
            //print_r($list->toArray());die;
            $company_date = [];
             foreach ($list as $key => $val) {
                $month = date("m",strtotime($val['adddate']));
                $val['user_code'] = get_user_code($month,$val['id']);
                $val['contory'] =$val['contory']==1?'否':'是';//有效性
                
                 $company_date[$key] = $val;
             }
      
            
            $this->json($company_date,0,'',['count' => $list->total()]);
        }
        return $this->fetch();
    }


    //花名册
    function  mingce(){
        $print=input('get.print');
        if ($this->request->isAjax()||$print) {
            
            $data = [
                'key' => $this->request->get('key', '', 'trim'),
                'ids' => $this->request->get('ids', '', 'trim'),
                'starttime' => $this->request->get('starttime', '', 'trim'),
                'endtime' => $this->request->get('endtime', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval')
            ];
           

            $model=CustomerModel:: alias('c')-> join('insurance i','i.customer_id=c.id')->field('c.mobile,c.org_id,i.*')->order('c.id','desc');

            if($data['key']){
                $model->where('realname','like','%' . $data['key'] . '%');
            } 
            
            if($data['starttime']){
                $model->where('agreement_start_date','>=',  $data['starttime']  );
            } 
            if($data['endtime']){
                $model->where('agreement_end_date','<=',  $data['endtime']  );
            } 
            if ($data['ids']) {
                
                $model->where('c.id','in', $data['ids']);
                
            }

            if($print){
                $list = $model ->select();

            }else{
                $list = $model->paginate($data['limit'], false, ['query' => $data]);
            }
            // print_r($list);die;
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $val['sex']=$val['sex']==1?'男':'女';
                $val['hk_character']=$val['hk_character']==1?'农村':'城镇';//户籍性质
                $org_id[]=$val['org_id'];
                //$company_name = Db::table('think_company')->where("id = " . $val['company_id'])->value('name');
                //$customer_date[$key]['company_name'] = $company_name;
                // if($val['is_name_auth'] == 1){
                //     $customer_date[$key]['is_name_auth_zh'] = "已认证";
                // }else{
                //     $customer_date[$key]['is_name_auth_zh'] = "未认证";
                // }
                //$customer_data[$key]['performance_fee'] = round($val['performance_fee'], 2);
            }


            $Common=new \app\common\model\Common();
            if($org_id){
                $organize=$Common->selectindex(db('organize')->where('id','in',$org_id)->select());
                foreach ($customer_date as $key => $val) {
                    if(isset($organize[$val['org_id']])){
                        $customer_date[$key]['organizename'] =$organize[$val['org_id']]['name'];
                    }
                    
                }
            }
            if($print){
                $i=1;
                foreach ($customer_date as $k => $val) {
                    $datap[$i][0]=$customer_date[$k]['id'];
                    $datap[$i][1]=$customer_date[$k]['name'];
                    $datap[$i][2]=$customer_date[$k]['id_number'];
                    $datap[$i][3]=$customer_date[$k]['mobile'];
                    $datap[$i][4]=$customer_date[$k]['insurance_city'];
                    $datap[$i][5]=$customer_date[$k]['hk_character'];
                    $datap[$i][6]=$customer_date[$k]['agreement_start_date'];
                    $datap[$i][7]=$customer_date[$k]['job_title'];
                    $datap[$i][8]=$customer_date[$k]['agreement_start_date'];
                    $datap[$i][9]=$customer_date[$k]['agreement_end_date'];
                    $datap[$i][10]=$customer_date[$k]['insurance_base'];
                    $datap[$i][11]=$customer_date[$k]['insurance_start_month'];


                    $datap[$i][12]=$customer_date[$k]['fund_base'];
                    $datap[$i][13]=$customer_date[$k]['fund_start_month'];
                    $datap[$i][14]=$customer_date[$k]['fund_proportion'];


                    if(isset($customer_date[$k]['organizename'])){
                        $datap[$i][15]=$customer_date[$k]['organizename'];
                    }
                    $datap[$i][16]=$customer_date[$k]['remark']; 
                    $i++;
                }

                $headers=['编号','姓名','身份证号','联系方式','缴费地点','户籍性质','入职日期','职位', '合同起日期','合同止日期' ,'社保基数','社保开始年月' ,'公积金基数'  ,'公积金开始年月'  ,'公积金比例'  ,'委托代理机构'   ,'备注' ];

                export_excel($headers,$datap );

            }else{
                $this->json($customer_date,0,'',['count' => $list->total()]);
            }

            
            
        }

        $organize=db('organize')-> select();
        $this->assign('organize',$organize);
        return $this->fetch();

    }
    function mingcesubmit(){//设置委托代理机构
        $ids=input('get.ids');$organize=input('get.organize');
        if($organize&&$ids){
            db('customer')->where('id in('.$ids.')')->update(['org_id'=>$organize]);
            $this->success('成功'.$organize);
        }
        else{
            $this->error('请选择创客或者代理机构');
        }

    }

    

    //社保管理 基本信息
    function  customer(){
        $print=input('get.print');
        if ($this->request->isAjax()||$print) {
            
            $data = [
                'key' => $this->request->get('key', '', 'trim'),'idVal' => $this->request->get('idVal', '', 'trim'),
                'starttime' => $this->request->get('starttime', '', 'trim'),
                'endtime' => $this->request->get('endtime', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval')
            ];

            $model=CustomerModel:: alias('c')-> join('insurance i','i.customer_id=c.id')->field('c.* ,c.id cid,i.*');

            if($data['key']){
                $model->where('realname','like','%' . $data['key'] . '%');
            } 
            if($data['idVal']){
                $data['idVal']=intval(substr($data['idVal'], 3));//var_dump($data['idVal']);
               
                $model->where('c.id',$data['idVal']);
            }
            if($data['starttime']){
                $model->where('agreement_start_date','>=',  $data['starttime']  );
            } 
            if($data['endtime']){
                $model->where('agreement_end_date','<=',  $data['endtime']  );
            } 
 

            if($print){
                $list = $model ->select();

            }else{
                $list = $model->paginate($data['limit'], false, ['query' => $data]);
            }
             //print_r($list);die;
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $val['sex']=$val['sex']==1?'男':'女';
                $val['hk_character']=$val['hk_character']==1?'农村':'城镇';//户籍性质
                $val['is_fund_extra']=$val['is_fund_extra']==1?'是':'否';//补充公积金
 
                
                $month = date("m",strtotime($val['add_date']));
                $val['user_code'] = get_user_code($month,$val['cid']);  
               // $org_id[]=$val['org_id'];
                //$company_name = Db::table('think_company')->where("id = " . $val['company_id'])->value('name');
                //$customer_date[$key]['company_name'] = $company_name;
                // if($val['is_name_auth'] == 1){
                //     $customer_date[$key]['is_name_auth_zh'] = "已认证";
                // }else{
                //     $customer_date[$key]['is_name_auth_zh'] = "未认证";
                // }
                //$customer_data[$key]['performance_fee'] = round($val['performance_fee'], 2);
            }


            $Common=new \app\common\model\Common();
            // if($org_id){
            //     $organize=$Common->selectindex(db('organize')->where('id','in',$org_id)->select());
            //     foreach ($customer_date as $key => $val) {
            //         if(isset($organize[$val['org_id']])){
            //             $customer_date[$key]['organizename'] =$organize[$val['org_id']]['name'];
            //         }
                    
            //     }
            // }
            if($print){
                $i=1;
                foreach ($customer_date as $k => $val) {
                    $datap[$i][0]=$customer_date[$k]['id'];
                    $datap[$i][1]=$customer_date[$k]['name'];
                    $datap[$i][2]=$customer_date[$k]['id_number'];
                    $datap[$i][3]=$customer_date[$k]['hk_character'];
                    $datap[$i][4]=$customer_date[$k]['job_title'];

                    $datap[$i][5]=$customer_date[$k]['address'];
                    $datap[$i][6]=$customer_date[$k]['agreement_start_date'];
                    $datap[$i][7]=$customer_date[$k]['agreement_end_date'];

                    $datap[$i][8]=$customer_date[$k]['work_city'];
                    $datap[$i][9]=$customer_date[$k]['insurance_city'];
                    $datap[$i][10]=$customer_date[$k]['insurance_base'];
                    $datap[$i][11]=$customer_date[$k]['insurance_start_month'];
                    $datap[$i][12]=$customer_date[$k]['insurance_city'];

                    $datap[$i][13]=$customer_date[$k]['fund_base'];
                    $datap[$i][14]=$customer_date[$k]['fund_start_month'];
                    $datap[$i][15]=$customer_date[$k]['fund_proportion'];

 
                    $datap[$i][16]=$customer_date[$k]['fund_account']; 
                    $datap[$i][17]=$customer_date[$k]['is_fund_extra']; 
                    $datap[$i][18]=$customer_date[$k]['fund_extra_proportion']; 
                    $i++;
                }

                $headers=['编号','姓名','身份证号', '户籍性质','职位','居住地址', '合同起日期','合同止日期' ,'工作城市','社保城市','社保基数','社保开始年月' ,'公积金城市','公积金基数'  ,'公积金开始年月'  ,'公积金比例'  ,'补充公积金'   ,'补充公积金比例' ];


                export_excel($headers,$datap );

            }else{
                $this->json($customer_date,0,'',['count' => $list->total()]);
            }

            
            
        } 
        return $this->fetch();

    }


    
    /**
     * 企业用户列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function errorapply()
    {
        if ($this->request->isAjax()) {
            
           
            $list = db('anomalies')->select();
            //  print_r($list);die;
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                //$company_name = Db::table('think_company')->where("id = " . $val['company_id'])->value('name');
                //$customer_date[$key]['company_name'] = $company_name;
               
            }
            $this->json($customer_date,0,'' );
        }
        return $this->fetch();
    }

    public function uploaderrorapply()
    {
        if($this->request->isPost()){ 
            
            header("content-type:text/html;charset=utf-8");
            //上传excel文件
            $file = request()->file('file');
            //将文件保存到public/uploads目录下面
            $info = $file->validate(['size'=>1048576,'ext'=>'xls,xlsx'])->move( './uploads');//print_r($info);exit;
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
            if($row_num){
                db('anomalies')->where('id','>',0)->update([ 'type'=>1]);
            }
            $data = []; //数组形式获取表格数据
            for ($i=5; $i <=$row_num; $i++) {
                $data['id'] = $sheet->getCell("A".$i)->getValue();
                if($data['id']){


                    $data['month'] = $sheet->getCell("B".$i)->getValue();//所属年月
               
                    $data['numbers'] = $sheet->getCell("C".$i)->getValue();
                    $data['name'] = $sheet->getCell("D".$i)->getValue();
                    $data['idcode'] = $sheet->getCell("E".$i)->getValue();
                    $data['anomalies'] = $sheet->getCell("F".$i)->getValue();
                    $data['orgnize'] = $sheet->getCell("G".$i)->getValue(); 
                    //将数据保存到数据库
                    $res = db('anomalies')->insert($data);
                    }
            }
            return json(array('state' => 1, 'errmsg' => '导入成功', 'data'=>$data));
        }

        else{
            return $this->fetch();
        }
    }


    /**
     * 蜂鸟账单列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function fengniaoacc()
    {
        $print=input('get.print');
        if ($this->request->isAjax()||$print) {
            
            $data = [
                'key' => $this->request->get('key', '', 'trim'),'idVal' => $this->request->get('idVal', '', 'trim'),
                'starttime' => $this->request->get('starttime', '', 'trim'),
                'endtime' => $this->request->get('endtime', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval')
            ];

            $model=db('bill')->alias('b')-> join('insurance i','i.customer_id=b.numbers') ;

            if($data['key']){
                $model->where('realname','like','%' . $data['key'] . '%');
            } 
            if($data['idVal']){
                $data['idVal']=intval(substr($data['idVal'], 3));//var_dump($data['idVal']);
               
                $model->where('c.id',$data['idVal']);
            }
            if($data['starttime']){
                $model->where('add_date','>',  $data['starttime']  );
            } 
            if($data['endtime']){
                $model->where('add_date','<',  $data['endtime']  );
            } 
 

            if($print){
                $list = $model ->select();

            }else{
                $list = $model->paginate($data['limit'], false, ['query' => $data]);
            }
             //print_r($list);die;
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $customer_ids[]=$val['customer_id'];
            }


            $Common=new \app\common\model\Common();
            if($customer_ids){
                $customersarr=$Common->selectindex(db('customer')->where('id','in',$customer_ids)->field('id,org_id')->select());
                foreach ($customer_date as $key => $val) {
                    if(isset($customersarr[$val['customer_id']])){
                         
                        $customer_date[$key]['org_id'] =$org_id[] =$customersarr[$val['customer_id']]['org_id'];
                    }
                    
                }
            }

            if($org_id){
                $organize=$Common->selectindex(db('organize')->where('id','in',$org_id)->select());
                foreach ($customer_date as $key => $val) {
                    if(isset($organize[$val['org_id']])){
                        $customer_date[$key]['organizename'] =$organize[$val['org_id']]['name'];
                    }
                    
                }
            }
            if($print){
                $i=1;
                foreach ($customer_date as $k => $val) {
                    $datap[$i][0]=$customer_date[$k]['id'];
                    $datap[$i][1]=$customer_date[$k]['name'];
                    $datap[$i][2]=$customer_date[$k]['id_number'];
                    $datap[$i][3]=$customer_date[$k]['hk_character'];
                    $datap[$i][4]=$customer_date[$k]['job_title'];

                    $datap[$i][5]=$customer_date[$k]['address'];
                    $datap[$i][6]=$customer_date[$k]['agreement_start_date'];
                    $datap[$i][7]=$customer_date[$k]['agreement_end_date'];

                    $datap[$i][8]=$customer_date[$k]['work_city'];
                    $datap[$i][9]=$customer_date[$k]['insurance_city'];
                    $datap[$i][10]=$customer_date[$k]['insurance_base'];
                    $datap[$i][11]=$customer_date[$k]['insurance_start_month'];
                    $datap[$i][12]=$customer_date[$k]['insurance_city'];

                    $datap[$i][13]=$customer_date[$k]['fund_base'];
                    $datap[$i][14]=$customer_date[$k]['fund_start_month'];
                    $datap[$i][15]=$customer_date[$k]['fund_proportion'];

 
                    $datap[$i][16]=$customer_date[$k]['fund_account']; 
                    $datap[$i][17]=$customer_date[$k]['is_fund_extra']; 
                    $datap[$i][18]=$customer_date[$k]['fund_extra_proportion']; 
                    $i++;
                }

                $headers=['编号','姓名','身份证号', '户籍性质','职位','居住地址', '合同起日期','合同止日期' ,'工作城市','社保城市','社保基数','社保开始年月' ,'公积金城市','公积金基数'  ,'公积金开始年月'  ,'公积金比例'  ,'补充公积金'   ,'补充公积金比例' ];


                export_excel($headers,$datap );

            }else{
                $this->json($customer_date,0,'',['count' => $list->total()]);
            }

            
            
        } 
        return $this->fetch();
    }

    public function uploadfengniaoacc()///、、蜂鸟账单
    {
        if($this->request->isPost()){ 
            
            header("content-type:text/html;charset=utf-8");
            //上传excel文件
            $file = request()->file('file');
            //将文件保存到public/uploads目录下面
            $info = $file->validate(['size'=>1048576,'ext'=>'xls,xlsx'])->move( './uploads');//print_r($info);exit;
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
            if($row_num){
               /// db('bill')->where('id','>',0)->delete();
            }
            $data = []; //数组形式获取表格数据
            for ($i=4; $i <=$row_num; $i++) {
                $data['numbers'] = $sheet->getCell("B".$i)->getValue();//编号
           
                if($data['numbers']){ 
                    
               
                    $data['name'] = $sheet->getCell("C".$i)->getValue();
                    $data['idcode'] = trim($sheet->getCell("D".$i)->getValue());//身份证号

                    $insinfo=db('insurance')->alias('i')->join('__CUSTOMER__ c','c.id=i.customer_id','right')->field('c.id cid ,i.id,circle_regist,circle_close')->where('c.id_number',$data['idcode'])->find(); 
                    if($insinfo['cid']){
                        $data['insurance_id'] =intval($insinfo['id']);
                        if($insinfo['circle_regist']==0||$insinfo['circle_close']==1){
                            continue;
                        }
                    }else{
                        continue;
                    }
                    
                    $data['month'] = $sheet->getCell("I".$i)->getValue(); //帐单年月 
                    $data['insurens_month'] = $sheet->getCell("J".$i)->getValue();
                    $data['gjj_month'] = $sheet->getCell("K".$i)->getValue();///公积金费用所属月（缴纳月）

                    $data['old_number'] = $sheet->getCell("L".$i)->getValue(); //养老企业基数
 
                    $data['old_rate'] = $sheet->getCell("M".$i)->getValue();
                    $data['old_remittance'] = $sheet->getCell("N".$i)->getValue();///养老企业汇缴

                    $data['old_nump'] = $sheet->getCell("O".$i)->getValue(); //养老个人基数 
                    $data['old_ratep'] = $sheet->getCell("P".$i)->getValue();//养老个人比例 
                    $data['old_remittancep'] = $sheet->getCell("Q".$i)->getValue();///公积金费用所属月（缴纳月）

                    $data['old_total'] = $sheet->getCell("R".$i)->getValue(); //养老合计 
                    $data['medical_number'] = $sheet->getCell("S".$i)->getValue();//医疗企业基数 
                    $data['medical_rate'] = $sheet->getCell("T".$i)->getValue();///医疗企业比例

                    $data['medical_big'] = $sheet->getCell("U".$i)->getValue(); //医疗企业大病 
                    $data['medical_remittance'] = $sheet->getCell("V".$i)->getValue();//医疗企业汇缴 
                    $data['medical_numberp'] = $sheet->getCell("W".$i)->getValue();///医疗个人基数 

                    $data['medical_ratep'] = $sheet->getCell("X".$i)->getValue(); //医疗个人比例 
                    $data['medical_bigp'] = $sheet->getCell("Y".$i)->getValue();//医疗个人大病 
                    $data['medical_remittancep'] = $sheet->getCell("Z".$i)->getValue();///医疗个人汇缴

                    $data['medical_total'] = $sheet->getCell("AA".$i)->getValue(); //医疗合计 
                    $data['unemployed'] = $sheet->getCell("AB".$i)->getValue();//失业企业基数 
                    $data['unemployed_rate'] = $sheet->getCell("AC".$i)->getValue();///失业企业比例
                    $data['unemployed_ra'] = $sheet->getCell("AD".$i)->getValue(); //    失业单位汇缴 
                    $data['unemployed_p'] = $sheet->getCell("AE".$i)->getValue();//失业个人基数 
                    $data['unemployed_ratep'] = $sheet->getCell("AF".$i)->getValue();///失业个人比例

                    $data['unemployed_rap'] = $sheet->getCell("AG".$i)->getValue(); //失业个人汇缴 
                    $data['unemployed_total'] = $sheet->getCell("AH".$i)->getValue();//失业合计 
                    $data['birth_number'] = $sheet->getCell("AI".$i)->getValue();///生育企业基数
                    $data['birth_rate'] = $sheet->getCell("AJ".$i)->getValue(); //生育企业比例 
                    $data['birth_ra'] = $sheet->getCell("AK".$i)->getValue();//生育企业汇缴 
                    $data['injury_number'] = $sheet->getCell("AL".$i)->getValue();///工伤企业基数
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    $data['injury_rate'] = $sheet->getCell("AM".$i)->getValue(); //工伤企业比例 
                    $data['injury_ra'] = $sheet->getCell("AN".$i)->getValue();//工伤企业汇缴 
                    $data['security_e'] = $sheet->getCell("AO".$i)->getValue();///单位社保
                    $data['security_p'] = $sheet->getCell("AP".$i)->getValue(); //个人社保 
                    $data['security_total'] = $sheet->getCell("AQ".$i)->getValue();//社保合计 
                    $data['gjj_enumber'] = $sheet->getCell("AR".$i)->getValue();///公积金企业基数

                    $data['gjj_erate'] = $sheet->getCell("AS".$i)->getValue(); //公积金企业比例 
                    $data['gjj_era'] = $sheet->getCell("AT".$i)->getValue();//公积金企业汇缴 
                    $data['gjj_numberp'] = $sheet->getCell("AU".$i)->getValue();///公积金个人基数
                    $data['gjj_ratep'] = $sheet->getCell("AV".$i)->getValue(); //公积金个人比例 
                    $data['gjj_rap'] = $sheet->getCell("AW".$i)->getValue();//公积金个人汇缴 
                    $data['gjj_total'] = $sheet->getCell("AX".$i)->getValue();///公积金合计
                    $data['socile_gjj'] = $sheet->getCell("AY".$i)->getValue(); //社保公积金小计 
                    $data['insurance_pay'] = $sheet->getCell("AZ".$i)->getValue();//残保金企业缴纳 
                    $data['subtotal'] = $sheet->getCell("BA".$i)->getValue();///小计
                    $data['service'] = $sheet->getCell("BB".$i)->getValue(); // 服务费 
                    $data['fees'] = $sheet->getCell("BC".$i)->getValue();//补缴滞纳金 
                    $data['totalmoney'] = $sheet->getCell("BD".$i)->getValue();///合计

 
                    
                    //将数据保存到数据库
                    $res = db('bill')->insert($data);
                }
            }
            return json(array('state' => 1, 'errmsg' => '导入成功', 'data'=>$data));
        }

        else{
            return $this->fetch();
        }
    }

    /**
     * 创客扣缴异常名录（
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function errkou()
    {
         $print=input('get.print');
        if ($this->request->isAjax()||$print) {
            
            $data = [
                'key' => $this->request->get('key', '', 'trim'), 
                'limit' => $this->request->get('limit', 10, 'intval')
            ];

            $model=db('bill')->alias('b')->field('i.*,b.month,b.id bid')-> join('customer i','i.id=b.numbers')->where('is_error',1) ;

            $item_date = date("Ym", strtotime('-1 month')); // 获取上个月 
            $model->where('month','like','%' . input('get.item_date'). '%');
           
             
 
   
            if($print){
                $list = $model ->select();

            }else{
                $list = $model->paginate($data['limit'], false, ['query' => $data]);
            }
             //print_r($list);die;
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $org_id[]=$val['org_id'];
            }


            $Common=new \app\common\model\Common(); 
            if($org_id){
                $organize=$Common->selectindex(db('organize')->where('id','in',$org_id)->select());
                foreach ($customer_date as $key => $val) {
                    if(isset($organize[$val['org_id']])){
                        $customer_date[$key]['organizename'] =$organize[$val['org_id']]['name'];
                    }
                    
                }
            }
             
            if($print){ 
                $i=1;
                foreach ($customer_date as $k => $val) {
                    $datap[$i][0]=$i;
                    $datap[$i][1]=$customer_date[$k]['month'];
                    $datap[$i][2]=$customer_date[$k]['bid'];
                    $datap[$i][3]=$customer_date[$k]['username'];
                    $datap[$i][4]=$customer_date[$k]['id_number']; 
                    $datap[$i][5]=$customer_date[$k]['organizename']; 
 
                    $i++;
                } 
                $headers=['序号','所属年月','编号','姓名','身份证号' ,'委托代理机构' ];


                export_excel($headers,$datap );

            }else{
                $this->json($customer_date,0,'',['count' => $list->total()]);
            }

            
            
        } 
        return $this->fetch();
    }

    /**
     * 缴费成功名录
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function seccu_error()
    {
         $print=input('get.print');
        if ($this->request->isAjax()||$print) {
            
            $data = [
                'key' => $this->request->get('key', '', 'trim'), 
                'limit' => $this->request->get('limit', 10, 'intval')
            ];

            $model=db('bill')->alias('b')->field('i.*,b.month,b.id bid,b.totalmoney')-> join('customer i','i.id=b.numbers')->where(['is_error'=>0,'if_kou'=>1]) ;

            $item_date = date("Ym", strtotime('-1 month')); // 获取上个月 
            $model->where('month','like','%' . input('get.item_date'). '%');
           
             
 
   
            if($print){
                $list = $model ->select();

            }else{
                $list = $model->paginate($data['limit'], false, ['query' => $data]);
            }
             //print_r($list);die;
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $org_id[]=$val['org_id'];
            }


            $Common=new \app\common\model\Common(); 
            if($org_id){
                $organize=$Common->selectindex(db('organize')->where('id','in',$org_id)->select());
                foreach ($customer_date as $key => $val) {
                    if(isset($organize[$val['org_id']])){
                        $customer_date[$key]['organizename'] =$organize[$val['org_id']]['name'];
                    }
                    
                }
            }
             
            if($print){ 
                $i=1;
                foreach ($customer_date as $k => $val) {
                    $datap[$i][0]=$i;
                    $datap[$i][1]=$customer_date[$k]['month'];
                    $datap[$i][2]=$customer_date[$k]['bid'];
                    $datap[$i][3]=$customer_date[$k]['username'];
                    $datap[$i][4]=$customer_date[$k]['id_number']; 
                    $datap[$i][5]=$customer_date[$k]['organizename']; 
                    $datap[$i][6]=$customer_date[$k]['mobile']; 
                    $datap[$i][7]=$customer_date[$k]['totalmoney']; 
 
                    $i++;
                } 
                $headers=['序号','所属年月','编号','姓名','身份证号' ,'委托代理机构' ,'联系方式' ,'缴费金额'];


                export_excel($headers,$datap );

            }else{
                $this->json($customer_date,0,'',['count' => $list->total()]);
            }

            
            
        } 
        return $this->fetch();
    }

    

    function viewsign(){//签署文件
        $id = $this->request->get('id',0,'intval');
        if ($id) {
            $list= get_agreefiles($id); 
        }else{
            $list = [];
        }
        $this->assign('list', $list);
        return $this->fetch();
    }

    function downloadidcode(){//身份证下载
        $ids=input('get.ids'); 
        if($ids){
            $lists=db('customer')->field('id , id_pic_a,id_pic_b,id_number')->where('id in('.$ids.')')->select( );
            if($lists){
                foreach ($lists as $key => $value) {
                    if($value['id_pic_a']){
                        $path[]=UPLOAD_PATH.str_replace('/upload/','',substr($value['id_pic_a'],0,strripos($value['id_pic_a'],'/')));
                    }
                }
                if($path){ 
                    $zipfile=UPLOAD_PATH.'tmpzip'.date('d').'.zip';
                    $re=createzip($zipfile,$path);///var_dump($re);
                }
            }
            if($re){
                header("Content-type:application/zip");
                header("Accept-Ranges:bytes");
                header("Accept-Length:".filesize($zipfile));
                header("Content-Disposition: attachment; filename=idcode.zip");
                readfile($zipfile);


            }else{
                $this->success('没有身份证照片' );
            }
            exit;
        }
        else{
            $this->error('参数错误');
        }

    }

    function downloadxy(){//协议下载
        $ids=input('get.ids'); 
        if($ids){
            $lists=db('customer')->field('id ')->where('id in('.$ids.')')->select( );
            if($lists){
                foreach ($lists as $key => $value) {
                    if($value['id']){
                     echo   $tmp=realpath(UPLOAD_PATH. '/useragrees/'.$value['id'] );
                         if($tmp){
                            $path[]=UPLOAD_PATH. '/useragrees/'.$value['id'] ;
                         }
                        
                    }
                }
                if($path){ 
                    $zipfile=UPLOAD_PATH.'tmpzip'.date('d').'.zip';
                    $re=createzip($zipfile,$path);///var_dump($re);
                }
            }
            if($re){
                header("Content-type:application/zip");
                header("Accept-Ranges:bytes");
                header("Accept-Length:".filesize($zipfile));
                header("Content-Disposition: attachment; filename=idcode.zip");
                readfile($zipfile);


            }else{
                $this->success('没有协议' );
            }
            exit;
        }
        else{
            $this->error('参数错误');
        }

    }

    /**
     * 创客的工商注册A端上传营业执照至系统的完成时间（工商注册）
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function licenselog()
    { 
        if ($this->request->isAjax() ) {
            
            $data = [
                'key' => $this->request->get('key', '', 'trim'), 
                'limit' => $this->request->get('limit', 10, 'intval')
            ];

            $model=db('log') ->alias('a')->join('customer b','a.uid = b.id')->where([ 'a.type'=>1]) ; 
            if($print){
                $list = $model ->select();

            }else{
                $list = $model->paginate($data['limit'], false, ['query' => $data]);
            }
             //print_r($list);die;
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $uids[]=$val['uid'];
            }


            $Common=new \app\common\model\Common(); 
            if($uids){
                $organize=$Common->selectindex(db('customer')->where('id','in',$uids)->select());
                foreach ($customer_date as $key => $val) {
                    if(isset($organize[$val['uid']])){
                        $customer_date[$key]['realname'] =$organize[$val['uid']]['realname'];
                    }
                    
                }
            }
              
            $this->json($customer_date,0,'',['count' => $list->total()]);
           

            
            
        } 
        return $this->fetch();
    }
    function koukuan(){//确认扣款
        $day = date("d",time()); // 获取当天日期
        if($day > 15){ // 每月15日之后才可执行入账操作
            $item_date = date("Ym", strtotime('-1 month')); // 获取上个月
            $bills=db('bill')->field('id,totalmoney,numbers')->where(['month'=>$item_date,'if_kou'=>0 ])->select();////查询 
            if($bills){
                foreach ($bills as $key => $value) {
                    $customer_info = Db::table("think_customer")->where("id = " . $value['numbers'])->field("social_fee,fund_fee,is_substitute,circle_regist,circle_close,money")->find();
                    // 判断该创客是否在别的任务中缴纳过每月只收一次的费用（社保、公积金、代缴服务费）
                    if( ($customer_info['circle_regist']==1&&$customer_info['circle_close']==0)){
 
                        $total_substitute_fee =$value['totalmoney'];//print_r($billarrs); 
                        if($total_substitute_fee>0&&$total_substitute_fee<=$customer_info['money'] ){


                           // $total_substitute_fee = $customer_info['social_fee'] + $customer_info['fund_fee'] + 30;
                            $data2 = array(
                                'money' => $total_substitute_fee,
                                'use_type' => $item_date . "月社保代缴费用",
                                'user_id' => $value['numbers']
                            ); 
                            Db::table("think_payout")->insert($data2); // 插入支出记录表中 
                            Db::table("think_customer")->where("id = " . $value['numbers'])->setDec('money', $total_substitute_fee);
                            $succ[]=$value['id'];
                            
                        }else{
                            ///异常账单is_error
                            $errorid[]=$value['id'];
                            //
                        }
                    }
                }
                if($succ){//异常
                    db('bill')->where('id','in',$succ)->update([ 'if_kou'=>1]);
                }

                if($errorid){//异常
                    db('bill')->where('id','in',$errorid)->update(['is_error'=>1,'if_kou'=>1]);
                }
            }   
            $msg = Result::success('收款成功', url('/admin/companylist'));
            
        }else{
            $msg = Result::error('未到系统结算日期，请在每月15日之后再进行操作', null, ['token' => Request::token()]);
        }
        return json($msg);
    }

    function test(){
        $Common= new \app\common\model\Common;
        $xy=['x'=>100,'y'=>200,'w'=>40];
        $Common->pdfsign('C:\xampp\htdocs\upload\1.pdf', 'C:\xampp\htdocs\upload\2.pdf', 'C:\xampp\htdocs\upload\1.png',$xy);
    }

}