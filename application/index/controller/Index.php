<?php
namespace app\index\controller;
use EasyWeChat\Factory;
use think\Db;
use think\Exception;

class Index extends Common
{
    //判断用户状态
    public function ifrealnameAuth() {
        $user = Db::table('mp_user')->where('openid','=',$this->myinfo['openid'])->find();
        if(!$user['nickname'] || in_array($user['status'],[0,-1,-2])) {
            return ajax('用户未认证',20);
        }
        if($user['status'] == 2) {
            return ajax('已进入黑名单',19);
        }
        return ajax('已认证',1);
    }
    //获取个人信息
    public function getMyinfo() {
        $user = Db::table('mp_user')->where('openid','=',$this->myinfo['openid'])->find();
        if(!$user['nickname']) {
            return ajax([],16);
        }
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
        return ajax($info,1);
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
        if(!$user['nickname']) {
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
        $val['gender'] = input('post.gender',1);
        $val['city'] = input('post.city');
        $val['age'] = input('post.age');

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
        if(!in_array($val['gender'],[1,2])) {
            return ajax('非法参数gender',-3);
        }

        try {
            Db::table('mp_user')->where('openid','=',$this->myinfo['openid'])->update([
                'nickname' => $val['nickname'],
                'gender' => $val['gender'],
                'city' => $val['city'],
                'age' => $val['age'],
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

    public function collectFormid() {
//        $data['desc'] = input('post.desc');
        $data['formid'] = input('post.formid');
        $res = Db::table('mp_formid')->insert($data);
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
