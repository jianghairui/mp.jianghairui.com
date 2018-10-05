<?php
namespace app\admin\controller;
use think\Db;
use think\Exception;

class Index extends Common
{
    public function index() {
        return $this->fetch();
    }

    public function catelist()
    {
        $map['pid'] = 0;
        $list = Db::table('mp_cate')->where($map)->select();
        $count = count($list);
        $this->assign('catelist',$list);
        $this->assign('count',$count);
        return $this->fetch();
    }

    public function childlist()
    {
        $cate_id = input('param.cate_id') ? input('param.cate_id') : 0;

        $exist = Db::table('mp_cate')->where(['id'=>$cate_id])->find();
        if(!$exist) {
            $this->error('非法操作');
        }
        $map['pid'] = $cate_id;
        $list = Db::table('mp_cate')->where($map)->select();
        $count = count($list);
        $this->assign('catelist',$list);
        $this->assign('count',$count);
        $this->assign('cate',$exist);
        return $this->fetch();
    }

    public function cateadd() {
        $cate_id = input('param.cate_id') ? input('param.cate_id') : 0;
        if($cate_id == 0) {
            $this->assign('cate_name','顶级分类');
        }else {
            $exist = Db::table('mp_cate')->where(['id'=>$cate_id,'pid'=>0])->find();
            if(!$exist) {
                $this->error('非法操作');
            }
            $this->assign('cate_name',$exist['cate_name']);
        }
        $list = Db::table('mp_cate')->where(['pid'=>$cate_id])->select();
        $this->assign('catelist',$list);
        $this->assign('cate_id',$cate_id);
        return $this->fetch();
    }

    public function cateadd_post() {
        $val['cate_name'] = input('post.cate_name');
        $val['pid'] = input('post.pid');
        $this->checkPost($val);
        if($val['pid'] != 0) {
            $exist = Db::table('mp_cate')->where(['id'=>$val['pid'],'pid'=>0])->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
        }
        if($this->checkExist('mp_cate',$val)) {
            return ajax('分类已存在',-1);
        }

        foreach ($_FILES as $k=>$v) {
            if($v['name'] == '') {
                unset($_FILES[$k]);
            }
        }
        if(!empty($_FILES)) {
            $info = $this->upload(array_keys($_FILES)[0]);
            if($info['error'] === 0) {
                $val['cover'] = $info['data'];
            }else {
                return ajax($info['msg'],-1);
            }
        }
        $res = Db::table('mp_cate')->insert($val);
        if($res) {
            return ajax([]);
        }else {
            return ajax('添加失败',-1);
        }
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
        //        Db::table('one')->insert(['name'=>'张涛','age'=>24,'sex'=>1]);
//        $tableinfo = Db::table('one')->getLastInsID();
//        $where = [];
//        $where = ['name'=>['like','%an%'],'age'=>25];

//        $where['name'] = ['like','%an%'];
//        $where['age'] = ['eq',25];

//        $list = Db::table('one')->where($where)->whereOr(['sex'=>0])->select();
//        halt($list);
    }
}
