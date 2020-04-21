<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 15:05
 */

namespace app\admin\controller;

use app\admin\service\TasksService;
use app\admin\model\Tasks as TasksModel;
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
            ];
            if($data['pro_id'] != ""){
                $where = array("pro_id" => $data['pro_id']);
            }else{
                $where = "";
            }
            $list = TasksModel::withSearch(['name'], ['name' => $data['name']])->where($where)->order('id','desc')
                ->paginate($data['limit'], false, ['query' => $data]);
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $cuser=Db::table('think_customer')->field('realname,mobile')->find($val['cus_id']);
                $customer_date[$key]['cus_name'] = $cuser['realname'];
                $customer_date[$key]['mobile'] = $cuser['mobile'];
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
                $list['cus_name'] = Db::table('think_customer')->where("id = " . $list['cus_id'])->value('realname');
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
            //$item_info = Db::table("think_item")->where("name = '" . $sheet->getCell("B".$i)->getValue() . "'")->find();
            $item_info = Db::table("think_item")->where("id = '" . $sheet->getCell("B".$i)->getValue() . "'")->find();
            //$cus_info = Db::table("think_customer")->where("realname = '" . $sheet->getCell("C".$i)->getValue() . "' and id_number = '" . $sheet->getCell("D".$i)->getValue() . "'")->value("id");
            $company_id = Db::table("think_project")->where("id = '" . $item_info['pro_id'] . "'")->value("company_id");
            $data['name'] = $sheet->getCell("A".$i)->getValue();
            $data['pro_id'] = $item_info['pro_id'];
            $data['item_id'] = $item_info['id'];
            $data['cus_id'] = $sheet->getCell("C".$i)->getValue();
            $data['company_id'] = $company_id;
            $data['item_date'] = $item_info['item_date'];
            $data['performance_fee'] = $sheet->getCell("D".$i)->getValue();
            $data['add_time'] = date("Y-m-d H:i:s");
            //将数据保存到数据库
            $res = TasksModel::insert($data);
            if($res){
                Db::table("think_item")->where("id = " . $item_info['id'])->update(array("has_tasks" => 1));
            }
        }
        return json(array('state' => 1, 'errmsg' => '导入成功', 'data'=>$data));
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
        // 获取创客列表
        $cusList = Db::table('think_customer')->where('deleted = 0')->field('id,realname')->select();
        // 获取企业列表
        $comList = Db::table('think_company')->where('deleted = 0')->field('id,name')->select();
        $this->assign('cusList',$cusList);
        $this->assign('comList',$comList);
        $print=input('get.print');
        if ($this->request->isAjax()||$print) {
            $data = [
                'limit' => $this->request->get('limit', 0, 'intval'),
                'key' => $this->request->get('key', ''),
                'cus_id' => $this->request->get('cus_id', ''),
                'date' => $this->request->get('date', ''),
                'company_id' => $this->request->get('company_id', ''),
                'status' => 1
            ];
            $where = array(
                "status" => 1
            );
            if($data['company_id'] != ''){
                $where["company_id"] = $data['company_id'];
            }
            if($data['cus_id'] != ''){
                $where["cus_id"] = $data['cus_id'];
            }
            if($data['date'] != ''){
                $where["item_date"] = $data['date'];
            }
            $mode=TasksModel::withSearch(['name'], ['name' => $data['key']])->where($where)->order('id','desc');
            if($print){
                $list = $mode ->select(); 
            }else{ 
                $list = 
                //->field("item_date,company_id,sum(real_fee) as total")
                //->group("item_date,company_id")
                $mode->paginate($data['limit'], false, ['query' => $data]);
            }
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $customer_date[$key]['company_name'] = Db::table('think_company')->where("id = " . $val['company_id'])->value('name');
                $customer_date[$key]['pro_name'] = Db::table('think_project')->where("id = " . $val['pro_id'])->value('name');
                $customer_date[$key]['item_name'] = Db::table('think_item')->where("id = " . $val['item_id'])->value('name');

                $cuser=Db::table('think_customer')->field('realname,mobile')->find($val['cus_id']);
                $customer_date[$key]['cus_name'] = $cuser['realname'];
                $customer_date[$key]['mobile'] = $cuser['mobile'];
            }
            if($print){
                $i=1;
                foreach ($list as $k => $val) {
                    $datap[$i][0]=$list[$k]['id'];
                    $datap[$i][1]=$list[$k]['company_name'];
                    $datap[$i][2]=$list[$k]['pro_id'];
                    $datap[$i][3]=$list[$k]['pro_name'];
                    $datap[$i][4]=$list[$k]['mobile'];
                    $datap[$i][5]=$list[$k]['item_name'];
                    $datap[$i][6]=$list[$k]['cus_id'];  
                    $datap[$i][7]=$customer_date[$k]['cus_name'];
                    $datap[$i][8]=$customer_date[$k]['performance_fee'];
                    $datap[$i][9]=$customer_date[$k]['real_fee']; 
                    $i++;
                }

                $headers=['编号','公司名称','项目ID','项目名称','手机号','项目明细','创客ID' ,'创客姓名' ,'预计绩效费' ,'实际绩效费' ];

                export_excel($headers,$datap );

            }else{
                $this->json($customer_date,0,'',['count' => $list->total()]);
            }
        }
        return $this->fetch();
    }
}