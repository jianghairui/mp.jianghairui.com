<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/19
 * Time: 19:55
 */
namespace app\admin\controller;
use think\Controller;
use my\Auth;
class Test extends Controller {

    protected $cmd;

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->cmd = request()->controller() . '/' . request()->action();

    }

    public function index() {
        $auth=new Auth();
//        $auth = Auth::instance();
        $check = $auth->check('Test/testlist',1);

    }

    public function test() {
        session_start();
        halt($_SESSION);
    }

    public function getUserList() {
        halt($this->cmd);

    }
}