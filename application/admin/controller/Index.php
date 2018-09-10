<?php
namespace app\admin\controller;

class Index
{
    public function index()
    {
        return 'OK!It is admin/index/index' . THINK_VERSION;
    }

    public function test() {
        echo 'LALALLA';
    }
}
