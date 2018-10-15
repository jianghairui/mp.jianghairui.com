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
    //会员列表
    public function memberlist() {
        $param['status'] = input('param.status','');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
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
            $where[] = ['nickname|realname|tel','like',"%{$param['search']}%"];
        }

        $count = Db::table('mp_user')->where($where)->count();
        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        $list = Db::table('mp_user')->where($where)->limit(($curr_page - 1)*$perpage,$perpage)->select();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('status',$param['status']);
        return $this->fetch();
    }
    //会员详情
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
    //通过认证
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
    //拒绝认证
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
    //批量通过认证
    public function multiPass() {
        $map[] = ['status','=',-1];
        $id_array = input('post.check');
        if(empty($id_array)) {
            return ajax('请选择认证对象',-1);
        }
        $map[] = ['id','in',$id_array];

        try {
            $res = Db::table('mp_user')->where($map)->update(['status'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax('共有' . $res . '条通过认证',1);
    }
    //拒绝通过认证
    public function multiReject() {
        $map[] = ['status','=',-1];
        $id_array = input('post.check');
        if(empty($id_array)) {
            return ajax('请选择认证对象',-1);
        }
        $map[] = ['id','in',$id_array];

        try {
            $res = Db::table('mp_user')->where($map)->update(['status'=>-2]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax('共有' . $res . '条未通过',1);
    }
    //拉黑用户
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
    //恢复用户
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
    //批量拉黑
    public function multiDel() {
        $map[] = ['status','<>',2];
        $id_array = input('post.check');
        if(empty($id_array)) {
            return ajax('请选择拉黑对象',-1);
        }
        $map[] = ['id','in',$id_array];

        try {
            $res = Db::table('mp_user')->where($map)->update(['status'=>2]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax('共拉黑' . $res . '个用户',1);
    }

    //获奖列表
    public function winnerlist() {
        $param['status'] = input('param.status','');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [];

        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['a.status','=',$param['status']];
        }

        if($param['logmin']) {
            $where[] = ['a.win_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['a.win_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }

        if($param['search']) {
            $where[] = ['u.nickname|u.tel','like',"%{$param['search']}%"];
        }

        $count = Db::table('mp_prize_actor')->alias('a')
            ->join("mp_user u","a.openid=u.openid",'left')
            ->join("mp_prize p","a.prize_id=p.id",'left')->where($where)->count();
        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        $list = Db::table('mp_prize_actor')->alias('a')
            ->join("mp_user u","a.openid=u.openid",'left')
            ->join("mp_prize p","a.prize_id=p.id",'left')
            ->where($where)
            ->field('a.*,u.nickname,u.avatar,u.tel,p.title')
            ->order(['a.win_time'=>'DESC'])
            ->limit(($curr_page - 1)*$perpage,$perpage)->select();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('status',$param['status']);
        return $this->fetch();
    }
    //发货
    public function sendPrize() {
        $id = input('post.id');
        $map[] = ['id','=',$id];
        $map[] = ['status','=',0];
        try {
            $res = Db::table('mp_prize_actor')->where($map)->update(['status'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($res) {
            return ajax([],1);
        }else {
            return ajax('操作失败',-1);
        }
    }




}