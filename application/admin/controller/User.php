<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 15:05
 */

namespace app\admin\controller;


use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\admin\model\AuthRule;
use app\admin\service\UserService;
use app\admin\service\AuthGroupService;
use app\admin\model\User as UserModel;

class User extends Common
{
    /**
     * 用户列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function userList()
    {
        $print=input('get.print');
        if ($this->request->isAjax()||$print) {
            $data = [
                'key' => $this->request->get('key', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
            ];

            $model= UserModel::withSearch(['name'], ['name' => $data['key']])
                ->hidden(['password']);

            if($print){
                $list = $model ->select();

            }else{ 
                $list =$model->paginate($data['limit'], false, ['query' => $data]);
            }
            $user_date = [];
            foreach ($list as $key => $val) {
                $user_date[$key] = $val;
                $user_date[$key]['title'] = $val->group_titles;
            }
            if($print){
                $i=1;
                foreach ($list as $k => $val) {
                    $datap[$i][0]=$list[$k]['uid'];
                    $datap[$i][1]=$list[$k]['user'];
                    $datap[$i][2]=$list[$k]['name'];
                    $datap[$i][3]=$list[$k]['title'];
                    $datap[$i][4]=$list[$k]['login_count'];
                    $datap[$i][5]=$list[$k]['last_login_ip'];
                    $datap[$i][6]=$list[$k]['last_login_time'];  
                    $i++;
                }

                $headers=['编号','账号','昵称','所属组','登陆次数登陆次数','最后登录IP','最后登陆时间' ];

                export_excel($headers,$datap );

            }else{
                $this->json($user_date,0,'',['count' => $list->total()]);
            }
        }
        return $this->fetch();
    }

    /**
     * 添加、编辑用户
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
            if ($data['uid']) {
                //编辑
                $res = UserService::edit($data);
                return $res;
            } else {
                //添加
                $data = UserService::add($data);
                return $data;
            }
        } else {
            $uid = $this->request->get('uid',0,'intval');
            if ($uid) {
                $list = UserModel::where('uid', '=', $uid)->find();
                $list['group_id'] = AuthGroupAccess::where('uid', '=', $uid)->column('group_id');
            }else{
                $list = array(
                    "status" => "1"
                );
            }
            $this->assign('list', $list);
            $grouplist = AuthGroup::column('id,title');
            $this->assign('grouplist', $grouplist);
            return $this->fetch();
        }
    }

    /**
     * 验证用户名是否存在
     * @return array
     * @throws \think\exception\DbException
     * @author 原点 <467490186@qq.com>
     */
    public function check()
    {
        $username = $this->request->get('username', '', 'trim');
        $res = UserModel::where('user', '=', $username)->field('uid')->find();
        if ($res) {
            $msg = ['code' => 1, 'msg' => '账号已存在'];
        } else {
            $msg = ['status' => 0, 'info' => '验证通过'];
        }
        return $msg;
    }

    /**
     * 删除用户
     * @author 原点 <467490186@qq.com>
     */
    public function delete()
    {
        $uid = $this->request->param('uid', 0, 'intval');
        if ($uid) {
            if ($uid != 1) {
                $res = UserService::delete($uid);
                return $res;
            } else {
                $this->error('无法删除超级管理员');
            }
        } else {
            $this->error('参数错误');
        }
    }

    /**
     * 用户组管理
     * @return array|mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function groupList()
    {
        if ($this->request->isPost()) {
            $id = $this->request->post('id', 0, 'intval');
            $type = $this->request->post('type', 0, 'intval');
            $title = $this->request->post('title', '', 'trim');
            $status = $this->request->post('status', 0, 'intval');
            $rules = $this->request->post('rules', []);
            switch ($type) {
                case 1://编辑、添加用户组
                    if ($id) {//编辑用户组
                        return AuthGroupService::edit($id, ['title' => $title]);
                    } else {//添加用户组
                        return AuthGroupService::add($title);
                    }
                    break;
                case 2://是否禁用用户组
                    return AuthGroupService::edit($id, ['status' => $status]);
                    break;
                case 3://获取权限列表
                    $list = AuthRule::field('id,pid,title as text')->where(['status'=>1])->select();
                    $data = list_to_tree($list->toArray(), 'id', 'pid', 'children');
                    return $data;
                    break;
                case 4://修改用户组权限
                    if (!$rules) $this->error('参数错误');
                    sort($rules);
                    $rules = implode(',', $rules);
                    $res = AuthGroupService::edit($id, ['rules' => $rules], true);
                    return $res;
                    break;
            }
        } else {
            if ($this->request->isAjax()) {
                $key = $this->request->get('key', '', 'trim');
                $limit = $this->request->get('key', 10, 'intval');
                $map = [];
                empty ($key) || $map[] = ['title', 'like', '%' . $key . '%'];
                $list = AuthGroup::where($map)->paginate($limit, false, ['query' => ['key' => $key], 'limit' => $limit]);
                $this->json($list->items(),0,'',['count' => $list->total()]);
            }
            return $this->fetch();
        }
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
            $uid = get_user_id();
            $res = UserService::editPassword($uid, $data['oldpassword'], $data['password']);
            return $res;
        } else {
            return $this->fetch();
        }
    }

}