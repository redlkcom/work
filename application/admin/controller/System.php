<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/28
 * Time: 13:20
 */

namespace app\admin\controller;

use app\admin\model\AuthRule;
use app\admin\model\Config;
use app\admin\model\LoginLog;
use think\facade\App;
use think\facade\Cache;

class System extends Common
{
    /**
     * 清除缓存
     * @author 原点 <467490186@qq.com>
     */
    public function cleanCache()
    {

        if (!$this->request->isPost()) {
            return $this->fetch();
        } else {
            $data = input();
            if (isset($data['path'])) {
                $file = App::getRuntimePath();
                foreach ($data['path'] as $key => $value) {
                    array_map('unlink', glob($file . $value . '/*.*'));
                    $dirs = (array)glob($file . $value . '/*');
                    foreach ($dirs as $dir) {
                        array_map('unlink', glob($dir . '/*'));
                    }
                    if ($dirs && $data['delete']) {
                        array_map('rmdir', $dirs);
                    }
                }
                $this->success('缓存清空成功');
            } else {
                $this->error('请选择清除的范围');
            }
        }
    }

    /**
     * 登录日志
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function loginLog()
    {
        if ($this->request->isAjax()) {
            $data = [
                'starttime' => $this->request->get('starttime', '', 'trim'),
                'endtime' => $this->request->get('endtime', '', 'trim'),
                'key' => $this->request->get('key', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval')
            ];
            $list = LoginLog::withSearch(['name', 'create_time'], [
                'name' => $data['key'],
                'create_time' => [$data['starttime'], $data['endtime']],
            ])->paginate($data['limit'], false, ['query' => $data]);
            $this->json($list->items(), 0, '', ['count' => $list->total()]);
        }
        return $this->fetch();
    }

    /**
     *系统菜单
     * @return mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function menu()
    {
        if ($this->request->isPost()) {
            $list = AuthRule::order('sort desc')->select();
            $this->json($list, 0, '获取成功');
        }
        return $this->fetch();
    }

    /**
     * 菜单编辑
     * @author 原点 <467490186@qq.com>
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editMenu()
    {
        if ($this->request->isPost()) {
            $data = [
                'name' => $this->request->post('name', '', 'trim'),
                'title' => $this->request->post('title', '', 'trim'),
                'pid' => $this->request->post('pid', 0, 'intval'),
                'status' => $this->request->post('status', 0, 'intval'),
                'menu' => $this->request->post('menu', '', 'trim'),
                'icon' => $this->request->post('icon', '', 'trim'),
                'sort' => $this->request->post('sort', 0, 'intval'),
            ];
            $id = $this->request->post('id', 0, 'intval');
            if ($id) { //编辑
                $res = AuthRule::where('id', '=', $id)->update($data);
            } else { //添加
                $res = AuthRule::create($data);
            }
            if ($res) {
                Cache::clear(config('auth.cache_tag'));//清除Auth类设置的缓存
                $this->success('保存成功', url('/admin/menu'));
            } else {
                $this->error('保存失败');
            }
        } else {
            $id = $this->request->param('id', 0, 'intval');
            if ($id) {
                $data = AuthRule::where('id', '=', $id)->find();
                $this->assign('data', $data);
            }
            $menu = AuthRule::where('pid', '=', 0)->order('sort desc')->column('id,title');
            $menu[0] = '顶级菜单';
            ksort($menu);
            $this->assign('menu', $menu);
            return $this->fetch();
        }
    }

    /**
     * 删除菜单
     * @author 原点 <467490186@qq.com>
     * @throws \Exception
     */
    public function deleteMenu()
    {
        $id = $this->request->post('id', 0, 'intval');
        empty($id) && $this->error('参数错误');
        if (AuthRule::where('pid', '=', $id)->count() > 0) {
            $this->error('该菜单存在子菜单,无法删除!');
        }
        $res = AuthRule::where('id', '=', $id)->delete();
        if ($res) {
            Cache::clear(config('auth.cache_tag'));//清除Auth类设置的缓存
            $this->success('删除成功', url('/admin/menu'));
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 配置管理
     * @return mixed|void
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function config()
    {
        if (!$this->request->isPost()) {
            $data = Config::where('name', 'system_config')->find();
            $this->assign('data', $data);
            return $this->fetch();
        } else {
            $save = [
                'value' => [
                    'debug' => $this->request->post('debug', 0, 'intval'),
                    'trace' => $this->request->post('trace', 0, 'intval'),
                    'trace_type' => $this->request->post('trace_type', 0, 'intval'),
                ],
                'status' => $this->request->post('status', 0, 'intval')
            ];
            $res = Config::update($save, ['name' => 'system_config']);
            if ($res) {
                cache('config', null);
                $this->success('修改成功', url('/admin/config'));
            } else {
                $this->error('修改失败');
            }
        }

    }

    /**
     * 站点配置
     * @return array|mixed|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function siteConfig()
    {
        if (!$this->request->isPost()) {
            $data = Config::where('name', 'site_config')->find();
            $this->assign('data', $data);
            return $this->fetch();
        } else {
            $save = [
                'value' => [
                    'title' => $this->request->post('title', '', 'trim'),
                    'name' => $this->request->post('name', '', 'trim'),
                    'copyright' => $this->request->post('copyright', '', 'trim'),
                    'icp' => $this->request->post('icp', '', 'trim')
                ],
            ];
            $res = Config::update($save, ['name' => 'site_config']);
            if ($res) {
                cache('site_config', null);
                $this->success('修改成功', url('/admin/siteConfig'));
            } else {
                $this->error('修改失败');
            }
        }
    }
    public function upload()
    {
        $this->getTransferInfo();die;
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
        // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            $path = $info->getExtension();
            // 成功上传后 返回上传信息
            return json(array('state' => 1, 'path' => $path));
        } else {
            // 上传失败返回错误信息
            return json(array('state' => 0, 'errmsg' => '上传失败'));
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
        $sign_url = 'http://127.0.0.1:4437/'; //请求签名
        $transfer_url = 'http://127.0.0.1:5777/'; //请求接口
        $packet_id = date("YmdHis") . rand(111111,999999);
        $bdate = date("Y-m-01");
        $edate = date('Y-m-d', strtotime(date('Y-m-01') . ' +1 month -1 day'));
        $sign_xml = "<body><lists name=\"acctList\"><list><acctNo>95200078801300000003</acctNo><beginDate>". $bdate ."</beginDate><endDate>". $edate ."</endDate><queryNumber>20</queryNumber><beginNumber>1</beginNumber><subAccount>6225160293976253</subAccount><subAcctName>浦发1339591801</subAcctName></list><lists></body>";
        
        $signature = $this->http_xml($sign_url,$sign_xml,array("Content-Type: INFOSEC_SIGN/1.0"));
        //echo $signature;die;
        $signature = "<?xml version=\"1.0\" encoding=\"GB2312\"?><info>" . $signature . "</info>";
        $xml_sign = simplexml_load_string($signature);
        //echo $xml_sign->html->body->sign;die;
        //header("Content-Type: text/html; charset=utf-8");
        //echo "我是谁" . $signature;die;
        //echo iconv("GB2312","UTF-8",$signature);die;
        $xml = "<?xml version=\"1.0\" encoding=\"gb2312\"?><packet><head><transCode>8924</transCode><signFlag>1</signFlag><masterID>2000040752</masterID><packetID>" . $packet_id . "</packetID><timeStamp>" . date("Y-m-d H:i:s") . "</timeStamp></head><body><signature>" . $xml_sign->html->body->sign . "</signature></body></packet>";
        echo "<script>" . $xml . "</script>";
        $xml_length = strlen($xml) + 6;
        echo $xml_length;
        $content_type = array("Content-Type: INFOSEC_VERIFY_SIGN/1.0","Content-Length:" . $xml_length);
        $res = $this->http_xml($transfer_url,$xml,$content_type);
        echo "<pre>";print_r($res);
    }

    /**
     * http请求方法
    */
    public function http_xml($url,$data,$content_type)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            //CURLOPT_PORT => "4437",
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $content_type,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
        echo "cURL Error #:" . $err;
        } else {
        return $response;
        }
    }
}