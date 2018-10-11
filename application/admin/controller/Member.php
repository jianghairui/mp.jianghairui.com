<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/25
 * Time: 16:09
 */
namespace app\admin\controller;
use think\Db;
class Member extends Common {

    public function memberlist() {
        $param['status'] = input('param.status','');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',config('app.page'));
        $perpage = input('param.perpage',10);

        $where = [];

        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['status','=',$param['status']];
        }

        if($param['logmin']) {
            $where[] = ['create_time','>=',strtotime(date('Y-m-d',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['create_time','<=',strtotime(date('Y-m-d',strtotime($param['logmax'])))];
        }

        if($param['search']) {
            $where[] = ['nickname|realanme','like',"%{$param['search']}%"];
        }

        $count = Db::table('mp_req')->where($where)->count();
        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        $list = Db::table('mp_user')->where($where)->limit(($curr_page - 1)*$perpage,$perpage)->select();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('status',$param['status']);
        return $this->fetch();
    }

    public function userPass() {
        $map[] = ['status','=',-1];
        $map[] = ['id','=',input('post.id',0)];

        $exist = Db::table('mp_user')->where($map)->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }
        try {
            Db::table('mp_user')->where($map)->update(['status'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function userReject() {
        $map[] = ['status','=',-1];
        $map[] = ['id','=',input('post.id',0)];

        $exist = Db::table('mp_user')->where($map)->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }
        try {
            Db::table('mp_user')->where($map)->update(['status'=>-2]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function winnerlist() {
        return $this->fetch();
    }




}