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
        $image = input('post.image');

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

        if(is_array($image) && !empty($image)) {
            if(count($image) != 3) {
                return ajax('必须上传三张图片',9);
            }
            foreach ($image as $v) {
                if(!file_exists($v)) {
                    return ajax($v,29);
                }
            }
        }
        $image_array = [];
        foreach ($image as $v) {
            $image_array[] = $this->rename_file($v,'static/uploads/identity/');
        }
        $val['identity_pic'] = serialize($image_array);

        $exist = Db::table('mp_userinfo')->where('openid','=',$val['openid'])->find();

        if($exist) {
            try {
                Db::table('mp_userinfo')->where('openid','=',$val['openid'])->update($val);
            }catch (\Exception $e) {
                foreach ($image_array as $v) {
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
                foreach ($image_array as $v) {
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

    //获取职业列表
    public function getJobList() {
        $where[] = ['status','=',1];
        $list = Db::table('mp_job')->where($where)->select();
        return ajax($list);
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











































    public function collectFormid() {
        $data['formid'] = input('post.formid');
        $res = Db::table('mp_formid')->insert($data);
        if($res) {
            return ajax(input('post.image'));
        }else {
            return ajax([],-1);
        }
    }

    public function test() {
        $v = 'static/tmp/200x150.jpg';
        if(!file_exists($v)) {
            die('file not exists');
        }
        $filename = substr(strrchr($v,"/"),1);
        $path = 'static/uploads/req/' . date('Y-m-d') . '/';
        is_dir($path) or mkdir($path,0755,true);
        $info = @rename($v, $path . $filename);
        halt($info);
    }









}
