<?php
namespace app\admin\controller;
use think\Db;
use think\Session;

class Index extends Common
{
    public function index() {
        return $this->fetch();
    }

    public function catelist()
    {

//        Db::table('one')->insert(['name'=>'张涛','age'=>24,'sex'=>1]);
//        $tableinfo = Db::table('one')->getLastInsID();
//        $where = [];
//        $where = ['name'=>['like','%an%'],'age'=>25];

//        $where['name'] = ['like','%an%'];
//        $where['age'] = ['eq',25];

//        $list = Db::table('one')->where($where)->whereOr(['sex'=>0])->select();
//        halt($list);
        return $this->fetch();
    }

    public function rlist() {
        return $this->fetch();
    }

    public function comment() {
        return $this->fetch();
    }

    public function feedback() {
        return $this->fetch();
    }

    public function personal() {
        return $this->fetch();
    }

    public function test() {
        echo 'LALALLA';
    }
}
