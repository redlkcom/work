<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 15:05
 */

namespace app\admin\controller;

use app\admin\service\ProjectService;
use app\admin\model\Project as ProjectModel;
use think\db;
use Env;
use PHPExcel_IOFactory;

class Project extends Common
{
    /**
     * 列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function index()
    {
        $print=input('get.print');
        if ($this->request->isAjax()||$print) {
            $data = [
                'name' => $this->request->get('key', '', 'trim'),
                'date' => $this->request->get('date', '', 'trim'),
                'company_id' => $this->request->get('company_id', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
            ];
            $list = ProjectModel::withSearch(['name'], ['name' => $data['name']])->order('id','desc');
            if($data['date'] != ''){
                $list = $list->where(array('start'=>array('EGT',$data['date'])));
            }
            if($data['company_id'] != ''){
                $list = $list->where(array("company_id" => $data['company_id']));
            }

            if($print){
                $list = $list ->select();

            }else{ 
                $list = $list->paginate($data['limit'], false, ['query' => $data]);
            }
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $company_name = Db::table('think_company')->where("id = " . $val['company_id'])->value('name');
                $customer_date[$key]['company_name'] = $company_name;
            }
            if($print){
                $i=1;
                foreach ($list as $k => $val) {
                    $datap[$i][0]=$list[$k]['id'];
                    $datap[$i][1]=$list[$k]['name'];
                    $datap[$i][2]=$list[$k]['company_name'];
                    $datap[$i][3]=$list[$k]['start'];
                    $datap[$i][4]=$list[$k]['end'];
                    $datap[$i][5]=$list[$k]['total_fee'];
                    $datap[$i][6]=$list[$k]['description'];  
                    $i++;
                }

                $headers=['编号','项目名称','所属公司','开始日期','结束日期','总费用','备注' ];

                export_excel($headers,$datap );

            }else{
                $this->json($customer_date,0,'',['count' => $list->total()]);
            }
        }
        $company_list = Db::name("company")->field("id,name")->select();
        $this->assign("company_list", $company_list);
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
                $res = ProjectService::edit($data);
                return $res;
            } else {
                //添加
                $res = ProjectService::add($data);
                return $res;
            }
        } else {
            $id = $this->request->get('id',0,'intval');
            if ($id) {
                $list = ProjectModel::where('id', '=', $id)->find();
            }else{
                $list = "";
            }
            $company_list = Db::table("think_company")->column("id,name");
            $customer_list = Db::table("think_customer")->column("id,realname");
            $this->assign('list', $list);
            $this->assign('company_list', $company_list);
            $this->assign('customer_list', $customer_list);
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
            $res = ProjectService::delete($id);
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
            //$company_id = Db::table("think_company")->where("name = '" . $sheet->getCell("B".$i)->getValue() . "'")->value("id");
            $data['name'] = $sheet->getCell("A".$i)->getValue();
            $data['company_id'] = $sheet->getCell("B".$i)->getValue();
            $data['start'] = $sheet->getCell("C".$i)->getValue();
            $data['end'] = $sheet->getCell("D".$i)->getValue();
            $data['total_fee'] = $sheet->getCell("E".$i)->getValue();
            $data['description'] = $sheet->getCell("F".$i)->getValue();
            $data['add_time'] = date("Y-m-d H:i:s");
            //将数据保存到数据库
            $res = ProjectModel::insertGetId($data);

        }
        return json(array('state' => 1, 'errmsg' => '导入成功', 'data'=>$data));
    }
}