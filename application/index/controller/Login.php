<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/20
 * Time: 16:36
 */
namespace app\index\controller;
use EasyWeChat\Factory;
use my\MyRedis;
use think\Db;

class Login extends Common {
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