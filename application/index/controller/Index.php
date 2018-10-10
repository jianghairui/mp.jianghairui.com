<?php
namespace app\index\controller;
use EasyWeChat\Factory;
use think\Db;
use think\Exception;

class Index extends Common
{

    //判断用户是否实名认证
    public function ifrealnameAuth() {
        $user = Db::table('mp_user')->where('openid','=',$this->myinfo['openid'])->find();
        if(!$user || in_array($user['status'],[0,-1,-2])) {
            return ajax('用户未认证',20);
        }
        if($user['status'] == 2) {
            return ajax('您的信誉太低',18);
        }
        if($user['status'] == 3) {
            return ajax('已进入黑名单',19);
        }
        return ajax('已认证',1);
    }
    //获取个人信息
    public function getMyinfo() {
        $where[] = ['u.openid','=',$this->myinfo['openid']];
        try {
            $info = Db::table('mp_user')->alias('u')
                ->join('mp_userinfo i','u.openid=i.openid','left')
                ->where($where)
                ->field('u.*,i.job,i.resume,i.career,i.identity_num,i.identity_pic')
                ->find();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($info) {
            return ajax($info,1);
        }else {
            return ajax([],16);
        }
    }
    //实名认证
    public function realnameAuth() {
        $realname = input('post.realname');
        $tel = input('post.tel');
        $val['identity_num'] = input('post.identity_num');
        $val['openid'] = $this->myinfo['openid'];
        $this->checkPost($val);

        if(!$realname) {
            return ajax([],-2);
        }

        if(!input_limit($realname,30)) {
            return ajax('姓名字数太多',15);
        }

        if(!isCreditNo_simple($val['identity_num'])) {
            return ajax('invalid identity_num',13);
        }

        if(!$tel || !is_tel($tel)) {
            return ajax('invalid tel',14);
        }

        $user = Db::table('mp_user')->where('openid','=',$val['openid'])->find();
        if(!$user) {
            return ajax([],16);
        }

        if($user['status'] != 0 && $user['status'] != -2) {
            return ajax('当前状态无法认证',17);
        }

        foreach ($_FILES as $k=>$v) {
            if($v['name'] == '') {
                unset($_FILES[$k]);
            }
        }
        if(count($_FILES) != 3) {
            return ajax('必须上传三张图片',9);
        }
        $info = $this->multi_upload('static/uploads/identity/');
        if($info['error'] === 0) {
            $val['identity_pic'] = serialize($info['data']);
        }else {
            return ajax($info['msg'],9);
        }

        $exist = Db::table('mp_userinfo')->where('openid','=',$val['openid'])->find();

        if($exist) {
            try {
                Db::table('mp_userinfo')->where('openid','=',$val['openid'])->update($val);

            }catch (\Exception $e) {
                foreach ($info['data'] as $v) {
                    @unlink($v);
                }
                return ajax($e->getMessage(),-1);
            }
            $delpics = unserialize($exist['identity_pic']);
            foreach ($delpics as $v) {
                @unlink($v);
            }
        }else {
            try {
                Db::table('mp_userinfo')->insert($val);
            }catch (\Exception $e) {
                foreach ($info['data'] as $v) {
                    @unlink($v);
                }
                return ajax($e->getMessage(),-1);
            }
        }

        try {
            Db::table('mp_user')->where('openid','=',$val['openid'])->update(['status'=>-1,'realname'=>$realname,'tel'=>$tel]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //完善个人信息
    public function completeInfo() {

        $val['nickname'] = input('post.nickname');
        $val['gender'] = input('post.gender');
        $val['city'] = input('post.city');

        $val['job'] = input('post.job');
        $val['resume'] = input('post.resume');
        $val['career'] = input('post.career');
        $this->checkPost($val);
        $this->checkUserAuth();

        if(!input_limit($val['nickname'],30)) {
            return ajax('昵称不超过30字',15);
        }
        if(!input_limit($val['resume'],200)) {
            return ajax('简历不超过200字',15);
        }
        if(!input_limit($val['career'],500)) {
            return ajax('工作经历不超过500字',15);
        }

        try {
            Db::table('mp_user')->where('openid','=',$this->myinfo['openid'])->update([
                'nickname' => $val['nickname'],
                'gender' => $val['gender'],
                'city' => $val['city'],
            ]);
        } catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        try {
            Db::table('mp_userinfo')->where('openid','=',$this->myinfo['openid'])->update([
                'job' => $val['job'],
                'resume' => $val['resume'],
                'career' => $val['career'],
            ]);
        } catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        return ajax([]);

    }


    public function myApply() {

    }

    public function myRelease() {

    }

    public function myChoice() {

    }

    public function myAccount() {

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






}
