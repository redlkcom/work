<?php

namespace app\index\controller;
use think\Controller;


class Index extends Controller
{
    public function index()
    {
        $this->error('管理员界面','/admin/index.html');
    }
}
