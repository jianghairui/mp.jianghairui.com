<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/27
 * Time: 11:24
 */
namespace app\admin\controller;
use think\Db;
use think\Session;

class Login extends Common {

    public function index() {
        if(session('username') && session('mploginstatus') && session('mploginstatus') == md5(session('username') . 'jiang')) {
            $this->redirect('Index/index');
            exit();
        }

        $cookie = cookie('mp_password');
        if(isset($cookie) && $cookie != '') {
            $data['mp_username'] = cookie('mp_username');
            $data['mp_password'] = cookie('mp_password');
            $data['remember_pwd'] = 1;
        }else {
            $data['mp_username'] = '';
            $data['mp_password'] = '';
            $data['remember_pwd'] = 0;
        }
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function login() {
        if(request()->isPost()) {
            $login_vcode = input('post.login_vcode');
            if($login_vcode !== session('login_vcode')) {
                $this->error('验证码错误',url('Login/index'));
            }
            session('login_vcode',null);
            $where['username'] = input('post.username');
            $where['password'] = md5(input('post.password') . config('login_key'));
            $result = Db::table('mp_admin')->where($where)->find();
            if($result) {
                session('mploginstatus',md5(input('post.username') . 'jiang'));
                session('admin_id',$result['id']);
                session('username',$result['username']);
                session('realname',$result['realname']);

                if(input('post.remember_pwd') == 1) {
                    cookie('mp_username',input('post.username'),3600*24*7);
                    cookie('mp_password',input('post.password'),3600*24*7);
                }else {
                    cookie('mp_username',null);
                    cookie('mp_password',null);
                }
                $this->redirect(url('Index/index'));
            }else {
                $this->error('用户名密码不匹配',url('Login/index'));
            }
        }
    }

    public function logout() {
        session(null);
        $this->redirect('Login/index');
    }

    public function vcode() {
        $vcode = generateVerify(200,50,2,4,24);
        session('login_vcode',$vcode);
    }




















//    public function sets() {
//        session('myname','jianghairui');
//        session('age','27');
//        cookie('cookie_name','viki',30);
//        cookie('cookie_sex','0',30);
//
//    }
//
//    public function gets() {
//        dump(session('myname'));
//        dump(session('age'));
//        dump(cookie('cookie_name'));
//        dump(cookie('cookie_sex'));
//    }



}