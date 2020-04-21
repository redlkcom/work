<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 15:05
 */

namespace app\admin_sp\controller;

use app\admin_sp\service\TasksService;
use app\admin_sp\model\Tasks as TasksModel;
use think\db;
use Env;
use PHPExcel_IOFactory;

class Tasks extends Common
{
    /**
     * 企业用户列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            $data = [
                'name' => $this->request->get('key', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
                'pro_id' => $this->request->get('pro_id', '', 'trim'),
                'company_id' => get_user_id_sp()
            ];
            $where['company_id'] = $data['company_id'];
            if($data['pro_id'] != ""){
                $where['pro_id'] = $data['pro_id'];
            } 
           // $where['status'] = ['<',5];
            $list = TasksModel::withSearch(['name'], ['name' => $data['name']])->where($where)->where('status','<',5)
                ->paginate($data['limit'], false, ['query' => $data]);
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $customer_date[$key]['cus_name'] = Db::table('think_customer')->where(["id"=>$val['cus_id']])->value('realname');
                if($val['status'] == 0){
                    $customer_date[$key]['status_zh'] = "未确认";
                }elseif($val['status'] == 1){
                    $customer_date[$key]['status_zh'] = "已确认";
                }elseif($val['status'] == 2){
                    $customer_date[$key]['status_zh'] = "沟通中";
                }
            }
            $this->json($customer_date,0,'',['count' => $list->total()]);
        }
        $this->assign("pro_id",$this->request->get('pro_id', '', 'trim'));
        return $this->fetch();
    }
     /**
     * 添加、编辑
     * @return mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['id']) {
                //编辑
                $res = TasksService::edit($data);
                return $res;
            } else {
                //添加
                $data = TasksService::add($data);
                return $data;
            }
        } else {
            $id = $this->request->get('id',0,'intval');
            if ($id) {
                $list = TasksModel::where('id', '=', $id)->find();
                $list['pro_name'] = Db::table('think_project')->where('id', '=', $list['pro_id'])->value("name");
                $list['cus_name'] = Db::table('think_customer')->where('id', '=', $list['cus_id'])->value("realname");
                if($list['status'] == 0){
                    $list['status_zh'] = "未确认";
                }elseif($list['status'] == 1){
                    $list['status_zh'] = "已确认";
                }elseif($list['status'] == 2){
                    $list['status_zh'] = "沟通中";
                }
            }else{
                $list = "";
            }
            $this->assign('list', $list);
            return $this->fetch();
        }
    }

    /**
     * 删除任务
     * @author bing点 <46274933301846@qq.com>
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $res = TasksService::delete($id);
            return $res;
        } else {
            $this->error('参数错误');
        }
    }

    // 批量导入
    public function importExcel()
    {
        $this->assign("pro_id",$_GET['pro_id']);
        return $this->fetch();
    }
    // 批量确认报酬
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
        $res_data = []; //数组形式获取表格数据
        $remark = "";
        for ($i=2; $i <=$row_num; $i++) {
            //$cus_id = Db::table("think_customer")->where("realname = '" . $sheet->getCell("B".$i)->getValue() . "' and id_number = '" . $sheet->getCell("D".$i)->getValue() . "'")->value("id");
            $res = DB::table("think_tasks")->where(array("id" => $sheet->getCell("A".$i)->getValue()))->field("id,name,performance_fee")->find();
            if($res){
                $data['real_fee'] = $sheet->getCell("E".$i)->getValue();
                if($res['performance_fee'] < $data['real_fee']){
                    $remark .= "《" . $res['name'] . "》";
                }else{
                    $data['status'] = "2";
                    $data['update_date'] = date("Y-m-d H:i:s");
                    TasksModel::where("id = " . $res['id'])->update($data);
                }
            }
        }
        if($remark != ""){
            $errmsg = "项目" . $remark . "由于实际绩效费大于协议绩效费未导入，请手动操作";
        }else{
            $errmsg = "导入成功";
        }
        return json(array('state' => 1, 'errmsg' => $errmsg, 'data'=>$res_data));
    }
    public function sendSalary()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $res = TasksService::sendSalary($data);
            return $res;
        } else {
            $id = $this->request->get('id',0,'intval');
            if ($id) {
                $list = TasksModel::where('id', '=', $id)->find();
                $list['pro_name'] = Db::table('think_project')->where('id', '=', $list['pro_id'])->value("name");
                $list['cus_name'] = Db::table('think_customer')->where('id', '=', $list['cus_id'])->value("realname");
                if($list['status'] == 0){
                    $list['status_zh'] = "未确认";
                }elseif($list['status'] == 1){
                    $list['status_zh'] = "已确认";
                }elseif($list['status'] == 2){
                    $list['status_zh'] = "沟通中";
                }
            }else{
                $list = "";
            }
            $this->assign('list', $list);
            return $this->fetch();
        }
    }

    /**
     * 任务报表
     */
    public function report()
    {
        $company_id = get_user_id_sp();
        // 获取创客列表
        $cusList = Db::view('customer','id,realname')
                    ->distinct(true)
                    ->view('tasks','company_id','tasks.cus_id = customer.id')
                    ->where(array('tasks.company_id'=>$company_id))
                    ->select();
        $this->assign('cusList',$cusList);
        if ($this->request->isAjax()) {
            $data = [
                'limit' => $this->request->get('limit', 0, 'intval'),
                'key' => $this->request->get('key', ''),
                'cus_id' => $this->request->get('cus_id', ''),
                'date' => $this->request->get('date', ''),
                'company_id' => $company_id,
                'status' => 1
            ];
            $where = array(
                "company_id" => $data['company_id'],
                "status" => 1
            );
            if($data['cus_id'] != ''){
                $where["cus_id"] = $data['cus_id'];
            }
            if($data['date'] != ''){
                $where["item_date"] = $data['date'];
            }
            $list = TasksModel::where($where);
            if($data['key'] != ''){
                $where["name"] = ["like",$data['key']."%"];
                $list = $list->whereLike('name',$data['key']."%");
            }
            $list = $list->paginate($data['limit'], false, ['query' => $data]);
            $customer_date = [];
            $company_name = Db::table('think_company')->where("id = " . $data['company_id'])->value('name');
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $customer_date[$key]['company_name'] = $company_name;
                $customer_date[$key]['pro_name'] = Db::table('think_project')->where("id = " . $val['pro_id'])->value('name');
                $customer_date[$key]['item_name'] = Db::table('think_item')->where("id = " . $val['item_id'])->value('name');
                $customer_date[$key]['cus_name'] = Db::table('think_customer')->where("id = " . $val['cus_id'])->value('realname');
            }
            $this->json($customer_date,0,'',['count' => $list->total()]);
        }
        return $this->fetch();
    }



    /**
     * 导入导出明细
     */
    public function importItem()
    {
        $this->assign("pro_id",$_GET['pro_id']);
        return $this->fetch();
    }


    /**
     * 导出任务数据
     */
    public function exportTasksExcel(){
        $xlsName  = "tasks";
        $xlsCell  = array(
            array('id','编号'),
            array('name','任务名称'),
            array('cus_name','分配创客'),
            array('item_date','项目日期'),
            array('performance_fee','实际绩效费')
        );
        //$nowTime = date('Ym', time());
        $nowTime = date('Ym', strtotime('-1 month'));
        $searchData = ['pro_id' => $this->request->get('pro_id', '', 'trim'),"item_date"=>$nowTime];
        // $xlsData = Db::table('think_tasks')->where($searchData)->field('id,name,item_date,performance_fee')->select();
        $xlsData = Db::view(["think_customer"=>"Customer"],["realname"=>"cus_name"])
                        ->view(["think_tasks"=>"Tasks"],"id,name,item_date,performance_fee","Tasks.cus_id = Customer.id")
                        ->where($searchData)
                        ->select();
        $this->exportExcel($xlsName,$xlsCell,$xlsData);
    }


    public function exportExcel($expTitle,$expCellName,$expTableData){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称

        $fileName =$xlsTitle . date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);

        // 引入核心文件
        require'../extend/PHPExcel/PHPExcel.php';
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        //$objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        // $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        foreach ($expTableData as $vk => $vv) {
            foreach ($expCellName as $ck => $cv) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$ck].($vk + 2), $vv[$cv[0]]);
            }
        }
        ob_end_clean();
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=UTF-8;name="'.$xlsTitle.'.xlsx"');
        header("Content-Disposition:attachment;filename=$fileName.xlsx");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    // 批量导入任务明细
    public function importData()
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
        $res_data = []; //数组形式获取表格数据
        $remark = "";
        for ($i=2; $i <=$row_num; $i++) {
            TasksModel::where(["item_id" => $sheet->getCell("A".$i)->getValue(),"status" => 0])->update(['status'=>5]);
        }
        for ($i=2; $i <=$row_num; $i++) {
            $item_info = Db::table("think_item")->where(["id" => $sheet->getCell("A".$i)->getValue()])->find();
            if($item_info){
                $ifregist=db('customer')->where(['circle_close'=>0,'circle_regist'=>1,"id" => $sheet->getCell("F".$i)->getValue()])->count();
                if($ifregist==0){////C创客在在未完成个体户工商注册转换为创客身份时，无法加入B端企业发布的任务
                    continue;
                }
                $has_tasks = TasksModel::where(["cus_id" => $sheet->getCell("F".$i)->getValue(), "item_id" => $item_info['id']])->where("status",">",0)->where("status","<",5)->count('id');
                if($has_tasks > 0){
                    continue;
                    return json(array('state' => 0, 'errmsg' => "创客已确认的任务不可修改，请修改后重新导入", 'data'=>$res_data));
                }
                
                $data['pro_id'] = $item_info['pro_id'];
                $data['item_id'] = $item_info['id'];
                $data['cus_id'] = $sheet->getCell("F".$i)->getValue();
                $data['company_id'] = get_user_id_sp();
                $data['name'] = $sheet->getCell("D".$i)->getValue();
                $data['item_date'] = $sheet->getCell("C".$i)->getValue();
                $data['performance_fee'] = $sheet->getCell("E".$i)->getValue();
                $data['status']='0';
                $res = TasksModel::insert($data);
                if(!$res){
                    $remark .= "《创客编号：" . $sheet->getCell("F".$i)->getValue() . "，任务明细名称：" . $sheet->getCell("D".$i)->getValue() . "》";
                }else{
                    Db::table("think_item")->where("id = " . $item_info['id'])->update(['has_tasks' => 1]);
                }
            }
        }
        if($remark != ""){
            $errmsg = $remark . "未导入，请手动操作";
        }else{
            $errmsg = "导入成功";
        }
        return json(array('state' => 1, 'errmsg' => $errmsg, 'data'=>$res_data));
    }
}