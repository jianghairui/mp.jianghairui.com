<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/19
 * Time: 20:59
 */
namespace app\admin\controller;
use think\Db;
class Admin extends Common {

    public function adminlist() {
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [];

        if($param['logmin']) {
            $where[] = ['a.create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['a.create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }

        if($param['search']) {
            $where[] = ['a.username|a.realname|a.tel','like',"%{$param['search']}%"];
        }

        $count = Db::table('mp_admin')->alias('a')->where($where)->count();
        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        $list = Db::table('mp_admin')->alias('a')
            ->join('mp_auth_group_access au','a.id=au.uid','left')
            ->join('mp_auth_group g','au.group_id=g.id','left')
            ->where($where)
            ->field('a.*,g.title')
            ->limit(($curr_page - 1)*$perpage,$perpage)->select();
        $this->assign('list',$list);
        $this->assign('page',$page);
        return $this->fetch();
    }

    public function adminadd() {
        $list = Db::table('mp_auth_group')->select();
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function adminadd_post() {
        $data = input('post.');
        $group_id = $data['group_id'];
        unset($data['group_id']);
        unset($data['password2']);
        $data['password'] = md5($data['password'] . config('login_key'));

        $exist = Db::table('mp_admin')->where('username',$data['username'])->find();
        if($exist) {
            return ajax('用户名已存在',-1);
        }
        try {
            Db::table('mp_admin')->insert($data);
            $id = Db::getLastInsID();
            Db::table('mp_auth_group_access')->insert(['uid'=>$id,'group_id'=>$group_id]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($data);
    }

    public function adminmod() {
        $id = input('param.id');
        if($id == 1) {
            return ajax('非法操作',-1);
        }
        $info = Db::table('mp_admin')->where('id',$id)->find();
        $group_id = Db::table('mp_auth_group_access')->where('uid',$id)->value('group_id');
        $list = Db::table('mp_auth_group')->select();
        $this->assign('list',$list);
        $this->assign('info',$info);
        $this->assign('group_id',$group_id);
        return $this->fetch();
    }

    public function admindel() {

    }

    public function adminStop() {

    }

    public function adminStart() {

    }

    public function grouplist() {
        return $this->fetch();
    }

    public function accesslist() {
        return $this->fetch();
    }


}