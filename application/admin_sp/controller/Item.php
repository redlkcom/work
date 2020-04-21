<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 15:05
 */

namespace app\admin_sp\controller;

use app\admin_sp\service\ItemService;
use app\admin_sp\model\Item as ItemModel;
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
     * @author dustin
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['company_id'] = get_user_id_sp();
            // 查询是否有任务 已分配任务不允许修改
            if($data['has_tasks'] == "1"){
                $this->error('已分配任务，不允许修改');
            }
            $res = ItemService::edit($data);
            return $res;
        } else {
            $id = $this->request->get('id',0,'intval');
            if ($id) {
                $list = ItemModel::where('id', '=', $id)->find();
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
            $res = ItemService::delete($id);
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


    public function exportItemExcel(){
        $xlsName  = "任务明细模板";
        $xlsCell  = array(
            array('id','编号'),
            array('pro_name','项目名称'),
            array('item_date','任务明细日期'),
            array('name','任务明细名称'),
            array('item_fee','预计绩效费'),
            array('tasks','分配创客（请填写创客编号，见后台创客列表）')
        );
        $searchData = ['pro_id' => $this->request->get('pro_id', '', 'trim'),"has_tasks"=>0];
        $xlsData = Db::view(['think_project'=>'Project'],['name'=>"pro_name"])
                    ->view(['think_item'=>'Item'],'id,name,item_date,item_fee,has_tasks', 'Item.pro_id = Project.id')
                    ->where($searchData)
                    ->select();
        if($xlsData){
            foreach ($xlsData as $k => $v){
                $xlsData[$k]['tasks'] =[[],[]];
            }
        }
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
            // 循环创客
            foreach ($vv['tasks'] as $tk => $tv) {
                foreach ($expCellName as $ck => $cv) {
                    if($cv[0] == 'name'){
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$ck].($vk + $vk+ $tk + 2), $vv[$cv[0]].($tk + 1));
                    }elseif($cv[0] == 'item_fee'){
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$ck].($vk + $vk+ $tk + 2), ($vv[$cv[0]]/2));
                    }elseif($cv[0] == 'tasks'){
                        $cus = '';
                        if($tv){
                            $cus = $tv['name'];
                        }
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$ck].($vk + $vk+ $tk + 2), $cus);
                    }else{
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$ck].($vk + $vk+ $tk + 2), $vv[$cv[0]]);
                    }
                }
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
}