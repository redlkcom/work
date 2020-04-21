<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 15:05
 */

namespace app\admin_sp\controller;

use app\admin_sp\service\CustomerService;
use app\admin_sp\service\CompanyService;
use app\admin_sp\model\Customer as CustomerModel;
use think\db;

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
                'realname' => $this->request->get('key', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
                'company_id' => get_user_id_sp(),
            ];
            // 连表 tasks + customer
            $list = Db::view('customer')->distinct(true)->view('tasks','company_id, cus_id','tasks.cus_id = customer.id');

            if($data['realname'] != ''){
                $list = $list->where(array("customer.realname" => $data['realname']));
            }
            $list = $list->where(array("tasks.company_id" => $data['company_id']));
            $count = $list->select();
            $list = $list->paginate($data['limit'], count($count), ['query' => $data]);

            $customer_date = [];
            foreach ($list as $key => $val) {
                $customer_date[$key] = $val;
                $company_name = Db::table('think_company')->where("id = " . $val['company_id'])->value('name');
                $customer_date[$key]['company_name'] = $company_name;
                if($val['is_name_auth'] == 1){
                    $customer_date[$key]['is_name_auth_zh'] = "已认证";
                }else{
                    $customer_date[$key]['is_name_auth_zh'] = "未认证";
                }
                $customer_data[$key]['performance_fee'] = $val['performance_fee'] + 0;
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
            $data = $this->request->post();
            if ($data['id']) {
                //编辑
                $res = CustomerService::edit($data);
                return $res;
            } else {
                //添加
                $data = CustomerService::add($data);
                return $data;
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
     * 修改密码
     * @return array|mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\Exception\DbException
     */
    public function editPassword()
    {
        if ($this->request->isPost()) {
            $data = input();
            $uid = $this->uid;
            $res = CompanyService::editPassword($uid, $data['oldpassword'], $data['password']);
            return $res;
        } else {
            return $this->fetch();
        }
    }
}