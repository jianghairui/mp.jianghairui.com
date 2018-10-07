<?php
namespace app\index\controller;
use EasyWeChat\Factory;
use think\Db;

class Index extends Common
{
    public function getCitylist() {
        $citylist = Db::table('mp_city')->select();
        $data['citylist'] = $this->sortMerge($citylist,0);
        $catelist = Db::table('mp_cate')->select();
        $data['catelist'] = $this->sortMerge($catelist,0);
        return ajax($data);
    }

    public function getRlist()
    {
        $this->checkPost(input('post.'));
        //return 'OK!It is Index/index';
    }

    public function getCatelist() {

    }



    public function setcache() {
        mredis()->set('arr',array('username'=>'Viki','sex'=>0),30);
//        $third_session = exec('/usr/bin/head -n 80 /dev/urandom | tr -dc A-Za-z0-9 | head -c 168');
//        halt($third_session);
    }

    public function getcache() {
        $name = mredis()->get("QWm1MXpErOl4Icqt6W7Ac1wzAxWweNVjNJULmPujYdlG6ZI358mCO1Bsgv3QHPWgTHe7sMv6qNT89cYfM4FOS4NfkxOabtk2s7ZfntI0RsdaDR00L9m3DVmfjPrjp3kDuNbCwAox7NWrrF8ft0KjzQqJSZi1tjVKVq9z6IkU");
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


//发送消息模板
    public function sendTpl() {
//        die(APP_PATH);

//        $data = input('post.');
//        $code = $data['code'];
        $this->mp_config = [
            'app_id' => 'wx0d6f8a78265b1229',
            'secret' => 'b7cfdce371e0c100e7fc1d482933d7f5',

            'mch_id'             => '1514516351',
            'key'                => 'LDB15083727504163447056815279712',   // API 密钥

            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path'          =>  '/var/www/mp.jianghairui.com/public/cert/cert.pem', // XXX: 绝对路径！！！！
            'key_path'           =>  '/var/www/mp.jianghairui.com/public/cert/key.pem',      // XXX: 绝对路径！！！！

            // 下面为可选项,指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => APP_PATH . '/wechat.log',
            ],
        ];
        $app = Factory::miniProgram($this->mp_config);
        die();
//        $app = Factory::miniProgram($this->mp_config);
//
//        $result = $app->template_message->send([
//            'touser' => 'openid',
//            'template_id' => '80gz0yz1GdDlMeFstTf2GxTSMRbEgBtM1nIpYu4F17U',
//            'page' => 'index',
//            'form_id' => '1537431978893',
//            'data' => [
//                'keyword1' => date('Y-m-d H:i:s'),
//                'keyword2' => '吉圣客汉堡',
//                'keyword3' => '18.00',
//                'keyword4' => '1008611',
//            ]
//        ]);
//        halt($result);
    }

    private function sortMerge($node,$pid=0)
    {
        $arr = array();
        foreach($node as $key=>$v)
        {
            if($v['pid'] == $pid)
            {
                $v['child'] = $this->sortMerge($node,$v['id']);
                $arr[] = $v;
            }
        }
        return $arr;
    }




}
