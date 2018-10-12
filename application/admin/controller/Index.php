<?php
namespace app\admin\controller;
use think\Db;
use think\Exception;
class Index extends Common
{

    //首页
    public function index() {
        return $this->fetch();
    }
    //分类列表
    public function catelist()
    {
        $map[] = ['pid','=',0];
        $list = Db::table('mp_cate')->where($map)->select();
        $count = count($list);
        $this->assign('catelist',$list);
        $this->assign('count',$count);
        return $this->fetch();
    }
    //子分类列表
    public function childlist()
    {
        $cate_id = input('param.cate_id') ? input('param.cate_id') : 0;
        $page['query'] = http_build_query(input('param.'));
        $curr_page = input('param.page',config('app.page'));
        $perpage = input('param.perpage',config('app.perpage'));

        $map[] = ['id','=',$cate_id];
        $exist = Db::table('mp_cate')->where($map)->find();
        if(!$exist) {
            $this->error('非法操作');
        }
        $where[] = ['pid','=',$cate_id];
        $count = Db::table('mp_cate')->where($where)->count();
        $page['count'] = $count;
        $page['totalPage'] = ceil($count/$perpage);

        $list = Db::table('mp_cate')->where($where)->limit(($curr_page - 1)*$perpage,$perpage)->select();
        $page['curr'] = $curr_page;
        $this->assign('catelist',$list);
        $this->assign('page',$page);
        $this->assign('cate',$exist);

        return $this->fetch();
    }
    //添加分类页面
    public function cateadd() {
        $cate_id = input('param.cate_id') ? input('param.cate_id') : 0;
        if($cate_id == 0) {
            $this->assign('cate_name','顶级分类');
        }else {
            $where[] = ['id','=',$cate_id];
            $where[] = ['pid','=',0];
            $exist = Db::table('mp_cate')->where($where)->find();
            if(!$exist) {
                $this->error('非法操作');
            }
            $this->assign('cate_name',$exist['cate_name']);
        }
        $this->assign('cate_id',$cate_id);
        return $this->fetch();
    }
    //添加分类提交
    public function cateadd_post() {
        $val['cate_name'] = input('post.cate_name');
        $val['pid'] = input('post.pid');

        $this->checkPost($val);
        if($val['pid'] != 0) {
            $where[] = ['pid','=',0];
            $where[] = ['id','=',$val['pid']];
            $exist = Db::table('mp_cate')->where($where)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
        }
        $map[] = ['cate_name','=',$val['cate_name']];
        $map[] = ['pid','=',$val['pid']];
        if($this->checkExist('mp_cate',$map)) {
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
            if(isset($val['cover'])) {
                @unlink($val['cover']);
            }
            return ajax('添加失败',-1);
        }
    }
    //修改分类页面
    public function catemod() {
        $cate_id = input('param.cate_id') ? input('param.cate_id') : 0;
        $exist = Db::table('mp_cate')->where('id',$cate_id)->find();
        if(!$exist) {
            $this->error('非法操作');
        }
        $this->assign('cate',$exist);
        return $this->fetch();
    }
    //修改分类提交
    public function catemod_post() {
        $val['cate_name'] = input('post.cate_name');
        $val['id'] = input('post.cate_id');
        $this->checkPost($val);

        $exist = Db::table('mp_cate')->where('id',$val['id'])->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }

        if($this->checkExist('mp_cate',[
            ['cate_name','=',$val['cate_name']],
            ['id','<>',$val['id']]
        ])) {
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

        $res = Db::table('mp_cate')->update($val);
        if($res !== false) {
            if(!empty($_FILES)) {
                @unlink($exist['cover']);
            }
            return ajax([]);
        }else {
            if(!empty($_FILES)) {
                @unlink($val['cover']);
            }
            return ajax('修改失败',-1);
        }
    }
    //删除分类
    public function cate_del() {
        $val['id'] = input('post.cate_id');
        $this->checkPost($val);
        $exist = Db::table('mp_cate')->where('id',$val['id'])->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }
        $model = model('Cate');
        if($exist['pid'] == 0) {
            $child_ids = Db::table('mp_cate')->where('pid','eq',$val['id'])->column('id');
            try {
                $model::destroy($child_ids);
            }catch (Exception $e) {
                return ajax($e->getMessage(),-1);
            }
        }
        try {
            $model::destroy($val['id']);
        }catch (Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        return ajax([],1);
    }
    //停用分类
    public function cate_stop() {
        $val['id'] = input('post.cate_id');
        $this->checkPost($val);
        $exist = Db::table('mp_cate')->where('id',$val['id'])->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }

        $res = Db::table('mp_cate')->where('id',$val['id'])->update(['status'=>0]);
        if($res !== false) {
            if($exist['pid'] == 0) {
                Db::table('mp_cate')->where('pid',$val['id'])->update(['status'=>0]);
            }
            return ajax([],1);
        }else {
            return ajax([],-1);
        }
    }
    //启用分类
    public function cate_start() {
        $val['id'] = input('post.cate_id');
        $this->checkPost($val);
        $exist = Db::table('mp_cate')->where('id',$val['id'])->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }

        $res = Db::table('mp_cate')->where('id',$val['id'])->update(['status'=>1]);
        if($res !== false) {
            if($exist['pid'] == 0) {
                Db::table('mp_cate')->where('pid',$val['id'])->update(['status'=>1]);
            }
            return ajax([],1);
        }else {
            return ajax([],-1);
        }
    }


    public function rlist() {
        $param['status'] = input('param.status','');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',config('app.page'));
        $perpage = input('param.perpage',10);

        $where = [];
        $where[] = ['pay_status','=',1];

        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['status','=',$param['status']];
        }

        if($param['logmin']) {
            $where[] = ['create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }

        if($param['search']) {
            $where[] = ['title','like',"%{$param['search']}%"];
        }

        $count = Db::table('mp_req')->where($where)->count();
        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        $list = Db::table('mp_req')->where($where)->order(['id'=>'DESC'])->limit(($curr_page - 1)*$perpage,$perpage)->select();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('status',$param['status']);
        return $this->fetch();
    }

    public function detail() {
        $rid = input('param.rid');
        $info = Db::table('mp_req')->where('id','=',$rid)->find();
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function reqPass() {
        $map[] = ['status','=',0];
        $map[] = ['pay_status','=',1];
        $map[] = ['id','=',input('post.id',0)];

        $exist = Db::table('mp_req')->where($map)->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }

        try {
            Db::table('mp_req')->where($map)->update(['status'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function reqReject() {
        $map[] = ['status','=',0];
        $map[] = ['pay_status','=',1];
        $map[] = ['id','=',input('post.id',0)];

        $exist = Db::table('mp_req')->where($map)->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }

        try {
            Db::table('mp_req')->where($map)->update(['status'=>-1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function reqShow() {
        $map[] = ['id','=',input('post.id',0)];
        try {
            Db::table('mp_req')->where($map)->update(['show'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function reqHide() {
        $map[] = ['id','=',input('post.id',0)];
        try {
            Db::table('mp_req')->where($map)->update(['show'=>0]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }































    public function test() {
        $map[] = ['id','between','38,45'];
        $res = Db::table('mp_cate')->where($map)->select();
        halt($res);
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
