<?php
namespace app\index\controller;
use EasyWeChat\Factory;
use my\MyRedis;
use think\Db;

class Index extends Common
{
    public function index()
    {
        $data = input('post.');
        $code = $data['code'];

        $app = Factory::miniProgram($this->mp_config);
        $info = $app->auth->session($code);

        $result = $app->template_message->send([
            'touser' => $info['openid'],
            'template_id' => '80gz0yz1GdDlMeFstTf2GxTSMRbEgBtM1nIpYu4F17U',
            'page' => 'index',
            'form_id' => '1537431978893',
            'data' => [
                'keyword1' => date('Y-m-d H:i:s'),
                'keyword2' => '吉圣客汉堡',
                'keyword3' => '18.00',
                'keyword4' => '1008611',
            ],
        ]);
        return ajax($result);
        //return 'OK!It is Index/index';
    }

    public function setcache() {
        $redis=new MyRedis();
        $redis::set('username','Viki',10);
//        $third_session = exec('/usr/bin/head -n 80 /dev/urandom | tr -dc A-Za-z0-9 | head -c 168');
//        halt($third_session);
    }

    public function getcache() {
        $redis = new MyRedis();
        $name = $redis::get('username');
        halt($name);
    }

    public function clearcache() {

    }

    public function postFormid() {
        $data['desc'] = input('post.desc');
        $data['formid'] = input('post.formid');
        $res = Db::table('formid')->insert($data);
        if($res) {
            return ajax($data);
        }else {
            return ajax([],-1);
        }
    }

    public function test() {
        $arr = ['name'=>'Jianghairu','sex'=>1];
        $age = $arr['age'];
//        halt($arr['age']);
//        die(ROOT_PATH);
//        $myfile = fopen(ROOT_PATH . "/newfile.txt", "w") or die("Unable to open file!");
//        $txt = "Bill Gates\n";
//        fwrite($myfile, $txt);
//        $txt = "Steve Jobs\n";
//        fwrite($myfile, $txt);
//        fclose($myfile);
    }





}
