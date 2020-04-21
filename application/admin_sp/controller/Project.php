<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 15:05
 */

namespace app\admin_sp\controller;

use app\admin_sp\service\ProjectService;
use app\admin_sp\model\Project as ProjectModel;
use app\admin_sp\model\Company as CompanyModel;
use app\admin_sp\model\Tasks;
use think\db;
use Env;

class Project extends Common
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
                'date' => $this->request->get('date', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
                'company_id' => get_user_id_sp(),
            ];
            $list = ProjectModel::withSearch(['name'], ['name' => $data['name']])->where(array("company_id" => $data['company_id']));
            if($data['date'] != ''){
                $list = $list->where(array('start'=>array('EGT',$data['date'])));
            }
            $list = $list->paginate($data['limit'], false, ['query' => $data]);
            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $company_name = Db::table('think_company')->where("id = " . $val['company_id'])->value('name');
                $customer_date[$key]['company_name'] = $company_name;
            }
            $this->json($customer_date,0,'',['count' => $list->total()]);
        }
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
            $data['company_id'] = get_user_id_sp();
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
            $this->assign('list', $list);
            return $this->fetch();
        }
    }

    /**
     * 费用总额
     * @return mixed
     * @author dustin
     */
    public function cost()
    {
        $user = session("user_auth_sp");
        $id = $user['id'];
        if($id){
            $company_name = CompanyModel::where("id = " . $id)->value("name");
            $gm_class = CompanyModel::where("id = " . $id)->value("gm_class");
            $gm_tax_rate = $gm_class == 2 ? 0.03 : 0.06;
            $item_date = date("Ym", strtotime('-1 month')); // 获取上个月
            $total_unconfirm_fee = Tasks::where([["company_id", "=", $id], ["status", "neq", 1], ["item_date", "=", $item_date]])->sum('real_fee'); // 查询上个月未确认的绩效费总额
            $total_item_fee = Tasks::where(['company_id' => $id, 'status' => 1, 'item_date' => $item_date])->sum('real_fee'); // 上月项目服务费总和
            $total_platform_fee = $total_item_fee * 0.05; // 上月平台服务费
            $total_taxes = ($total_item_fee + $total_platform_fee) * $gm_tax_rate; // 税费
            $total_fee = $total_item_fee + $total_platform_fee + $total_taxes; // 上月需支付总额
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
            $res = ProjectModel::insert($data);
        }
        return json(array('state' => 1, 'errmsg' => '导入成功', 'data'=>$data));
    }



    public function exportExcel(){
        // $data = Db::name('project')->select();
        // $field = ['项目编号','项目名称','所属公司','开始日期','结束日期','总费用','发布时间','备注','创客'];
        // Common::exportExcel1($field, $data);
        $a = $_GET;
        $fileName = 'exportExcel.xls';
        require'../extend/PHPExcel/PHPExcel.php';
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                                    ->setLastModifiedBy("Maarten Balliauw")
                                    ->setTitle("aaaa")
                                    ->setSubject("asdasdasd")
                                    ->setDescription("说明")
                                    ->setKeywords("off")
                                    ->setCategory('file');
        // 设置头信息
        $objPHPExcel->setActiveSheetIndex(0);
        $key = ord("A");
        $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', '编号')
                ->setCellValue('B1', '项目名称')
                ->setCellValue('C1', '项目日期')
                ->setCellValue('D1', '项目报酬')
                ->setCellValue('E1', '分配创客');
        $searchData = ['pro_id' => $this->request->get('pro_id', '', 'trim')];
        $data = Db::table('think_item')->where($searchData)->field('id,name,item_date,item_fee')->select();

        $colum = 2;

        foreach ($data as $key => $rows) {

            $span = ord("A");
            foreach ($data[$key] as $keyName => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue(chr($span).$colum, $value);
                $span++;
            }
            $colum++;
            $span = ord("A");
            foreach ($data[$key] as $keyName => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue(chr($span).$colum, $value);
                $span++;
            }
            $colum++;
        }

        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save(EXPORT_PATH.$fileName);
        return json(array('state' => 1, 'msg' => 'excel生成成功<br>正在为你跳转下载页，请稍等...', 'data'=>$fileName));
        $filePath = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'export'.DIRECTORY_SEPARATOR.$fileName;
        // self::downloadFile($filePath,'aa.xls');

    }


    public function downloadFile($filePath,$saveAsFileName){

        //输出内容
         //到浏览器
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$saveAsFileName.'.xls"');
        header("Content-Transfer-Encoding: binary");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $objWriter->save('php://output');
    }
}