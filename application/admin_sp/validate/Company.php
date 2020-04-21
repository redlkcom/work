<?php
/**
 * Created by PhpStorm.
 * User: yuandian
 * Date: 2016/9/9
 * Time: 15:39
 */

namespace app\admin_sp\validate;

use think\Validate;

class Company extends Validate
{
    protected $rule = [
        'user_name' => 'require|max:25|unique:Company',
        'password' => 'require|length:6,25',
        // 'code' => 'require|captcha'
        'name'=>'require',
        'social_code'=>'require',
        'reg_address'=>'require',
        'ope_address'=>'require',
        'bank_name'=>'require',
        'bank_account'=>'require',
        'contact_name'=>'require',
        'mobile'=>'require',
        //'reg_date'=>'require',
        //'end_date'=>'require',
    ];

    protected $message = [
        'user_name.require' => '用户名不能为空',
        'user_name.unique' => '用户名已注册',
        'user_name.length' => '用户名长度2-25位',
        'password.require' => '密码不能为空',
        'password.length' => '密码长度6-25位',
        'name.require' => '公司名称不能为空',
        'social_code.require' => '社会统一信用代码不能为空',
        'reg_address.require' => '注册地址不能为空',
        'ope_address.require' => '经营地址不能为空',
        'bank_name.require' => '开户行名称不能为空',
        'bank_account.require' => '银行账户不能为空',
        'contact_name.require' => '联系人不能为空',
        'mobile.require' => '联系电话不能为空',
        //'reg_date.require' => '注册时间不能为空',
        //'end_date.require' => '失效时间不能为空',
        // 'code.require' => '验证码不能为空',
        // 'code.captcha' => '验证码错误'
    ];

    protected $scene = [
        'add'  =>  ['user_name','password'],
        'edit' =>  ['name','social_code','reg_address','ope_address','bank_name','bank_account','contact_name','mobile','reg_date','end_date'],
    ];
}