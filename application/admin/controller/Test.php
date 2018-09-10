<?php
namespace app\admin\controller;
use think\Controller;
class Test extends Controller
{
    public function _initialize()
    {
        parent::_initialize();
//        echo '初始化函数<br>';
    }

    public function index()
    {
        return 'This is admin/test/index';
    }

    public function test()
    {
        return 'this is admin test/test';
    }

    public function data()
    {
        $this->success('跳转到admin/index/index','Index/index');
//        return json(['name'=>'姜海蕤',['a'=>'Apple','b'=>'beauty','c'=>'cando']]);
    }

}
