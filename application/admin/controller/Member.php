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
            $where[] = ['create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
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

    public function userdetail() {
        $where[] = ['u.id','=',input('param.id')];
        try {
            $info = Db::table('mp_user')->alias('u')
                ->join('mp_userinfo i','u.openid=i.openid','left')
                ->where($where)
                ->field('u.*,i.job,i.resume,i.career,i.identity_num,i.identity_pic')
                ->find();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function winnerlist() {
        $param['status'] = input('param.status','');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',config('app.page'));
        $perpage = input('param.perpage',10);

        $where = [];

        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['w.status','=',$param['status']];
        }

        if($param['logmin']) {
            $where[] = ['w.create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['w.create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }

        if($param['search']) {
            $where[] = ['u.nickname|u.tel','like',"%{$param['search']}%"];
        }

        $count = Db::table('mp_prize_winner')->where($where)->count();
        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        $list = Db::table('mp_prize_winner')->alias('w')
            ->join("mp_user u","w.openid=u.openid",'left')
            ->join("mp_prize p","w.prize_id=p.id",'left')
            ->where($where)
            ->field('w.*,u.nickname,u.avatar,u.tel,p.title')
            ->limit(($curr_page - 1)*$perpage,$perpage)->select();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('status',$param['status']);
        return $this->fetch();
    }

    public function sendPrize() {
        $id = input('post.id');
        $map[] = ['id','=',$id];
        $map[] = ['status','=',0];
        try {
            $res = Db::table('mp_prize_winner')->where($map)->update(['status'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($res) {
            return ajax([],1);
        }else {
            return ajax('操作失败',-1);
        }
    }

    public function userStop() {
        $id = input('post.id');
        $map[] = ['id','=',$id];
        try {
            $res = Db::table('mp_user')->where($map)->update(['status'=>2]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($res) {
            return ajax([],1);
        }else {
            return ajax('拉黑失败',-1);
        }
    }

    public function userGetback() {
        $id = input('post.id');
        $map[] = ['status','=',2];
        $map[] = ['id','=',$id];
        try {
            $res = Db::table('mp_user')->where($map)->update(['status'=>0]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($res) {
            return ajax([],1);
        }else {
            return ajax('恢复失败',-1);
        }
    }




}