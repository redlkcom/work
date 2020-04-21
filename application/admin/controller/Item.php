<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 15:05
 */

namespace app\admin\controller;

use app\admin\service\ItemService;
use app\admin\model\Item as ItemModel;
use think\db;
use Env;
use PHPExcel_IOFactory;

class Item extends Common
{
    /**
     * 
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
            $list = ItemModel::withSearch(['name'], ['name' => $data['name']])->where($where)
                ->paginate($data['limit'], false, ['query' => $data]);
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $project_name = Db::table('think_project')->where("id = " . $val['pro_id'])->value('name');
                $customer_date[$key]['project_name'] = $project_name;
                if($val['has_tasks'] == 1){
                    $customer_date[$key]['has_tasks_zh'] = "已分配";
                }else{
                    $customer_date[$key]['has_tasks_zh'] = "未分配";
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
                $res = ItemService::edit($data);
                return $res;
            } else {
                //添加
                $data = ItemService::add($data);
                return $data;
            }
        } else {
            $id = $this->request->get('id',0,'intval');
            if ($id) {
                $list = ItemModel::where('id', '=', $id)->find();
                $list['company_id'] = Db::table("think_project")->where("id = " . $list['pro_id'])->value("company_id");
                $list['cus_ids'] = Db::table("think_tasks")->where("item_id = " . $list['id'])->column("cus_id");
            }else{
                $list = "";
            }
            $customer_list = Db::table("think_customer")->column("id,realname");
            $this->assign('customer_list', $customer_list);
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
            $res = ItemService::delete($id);
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
            //$company_id = Db::table("think_project")->where("name = '" . $sheet->getCell("B".$i)->getValue() . "'")->value("id");
            $data['name'] = $sheet->getCell("A".$i)->getValue();
            $data['company_id'] = $sheet->getCell("B".$i)->getValue();
            $data['item_date'] = $sheet->getCell("C".$i)->getValue();
            $data['item_fee'] = $sheet->getCell("D".$i)->getValue();
            $data['add_time'] = date("Y-m-d H:i:s");
            //将数据保存到数据库
            $res = ItemModel::insertGetId($data);

        }
        return json(array('state' => 1, 'errmsg' => '导入成功', 'data'=>$data));
    }
}