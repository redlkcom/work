<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/9/7
 * Time: 10:00
 */

namespace app\admin\service;

use app\admin\model\Company;
use app\admin\model\AuthGroupAccess;
use think\facade\Request;
use app\admin\traits\Result;

class CompanyService
{
    use Result;
    /**
     * 添加用户
     * @param $data
     * @return array
     * @author 原点 <467490186@qq.com>
     * @throws \Exception
     */
    public static function add($data)
    {
        //验证数据合法性
        // $validate = validate('User');
        // if (!$validate->scene('add')->check($data)) {
        //     //令牌数据无效时重置令牌
        //     $validate->getError() != '令牌数据无效' ? $token = Request::token() : $token = '';
        //     $msg = Result::error($validate->getError(), null, ['token' => $token]);
        //     return $msg;
        // }
        $user = new Company;
        $user->sys_id = self::systemCode();                                            // 系统唯一编号
        $user->user_name = $data['user_name'];                                     // 后台登录用户名
        $user->name = $data['name'];                                                // 公司名称
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);    // 后台登录密码
        $user->social_code = $data['social_code'];                                // 社会统一代码
        $user->reg_address = $data['reg_address'];                                // 注册地址
        $user->ope_address = $data['ope_address'];                                // 经营地址
        $user->bank_name = $data['bank_name'];                                    //开户行
        $user->bank_account = $data['bank_account'];                             //开户行账号
        $user->contact_name = $data['contact_name'];                             //联系人
        $user->mobile = $data['mobile'];                                          //联系电话
        $user->add_date = date("Y-m-d H:i:s",time());                            //添加日期.
        $res = $user->save();
        if ($res) {
            $msg = Result::success('添加成功', url('/admin/companyList'));
        } else {
            $msg = Result::error('添加失败', null, ['token' => Request::token()]);
        }
        return $msg;
    }

    /**
     * 编辑用户
     * @param $data
     * @return array|string
     * @author 原点 <467490186@qq.com>
     * @throws \Exception
     */
    public static function edit($data)
    {
        $userdata = [
            'user_name' => $data['user_name'],
            'name' => $data['name'],
            'social_code' => $data['social_code'],
            'reg_address' => $data['reg_address'],
            'ope_address' => $data['ope_address'],
            'bank_name' => $data['bank_name'],
            'bank_account' => $data['bank_account'],
            'contact_name' => $data['contact_name'],
            'mobile' => $data['mobile']
        ];

        if($data['xy_pic']){$userdata['xy_pic']=$data['xy_pic'];}
        $res = Company::update($userdata, ['id' => $data['id']]);
        if ($res) {
            $msg = Result::success('编辑成功', url('/admin/companylist'));
        } else {
            $msg = Result::error('编辑失败');
        }
        return $msg;
    }

    /**
     * 删除用户
     * @param $uid 用户id
     * @return array|string
     * @author 原点 <467490186@qq.com>
     * @throws \Exception
     */
    public static function delete($id)
    {
        if (!$id) {
            return Result::error('参数错误');
        }
        $res = Company::destroy($id);
        if ($res) {
            $msg = Result::success('删除成功');
        } else {
            $msg = Result::error('删除失败');
        }
        return $msg;
    }

    /**
     * 生成系统唯一编号
     */
    public static function systemCode()
    {
        $char = "0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM";
        $i = 0;
        $j = strlen($char) - 1;
        $num = "";
        for($i;$i<8;$i++){
            $k = rand(0,$j);
            $num .= $char[$k];
        }
        $res = Company::where(['sys_id' => $num])->count('id');
        if($res > 0){
            self::systemCode();
        }else{
            return $num;
        }
    }
}