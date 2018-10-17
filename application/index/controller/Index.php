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


    //我申请的列表
    public function myApply() {

    }
    //我的发布列表
    public function myRelease() {

    }
    //接单的候选者列表
    public function myChoiceList() {

    }
    //选择一个接单人
    public function makeChoice() {

    }
    //我的账户余额
    public function myAccount() {
        $where[] = ['openid','=',$this->myinfo['openid']];
        try {
            $info = Db::table('mp_user')->where($where)->value('balance');
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($info,1);
    }
    //我的明细
    public function myAccountDetail() {
        $where[] = ['openid','=',$this->myinfo['openid']];
        try {
            $info = Db::table('mp_billing')->where($where)->field('openid',true)->select();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($info,1);
    }
    //申请提现
    public function withdrawApply() {
        $val['money'] = input('post.money');
        $val['form_id'] = input('post.formid');
        $openid = $this->myinfo['openid'];

        $this->checkPost($val);
        if(!is_currency($val['money'])) {
            return ajax([],5);
        }

        $setting = Db::table('mp_setting')->find();

        if(floatval($val['money']) < 1 || floatval($val['money']) < floatval($setting['minimum'])) {
            return ajax([],32);
        }
        $where[] = ['openid','=',$openid];
        $balance = Db::table('mp_user')->where($where)->value('balance');
        $withdraw_rate = $setting['withdraw_rate'];

        $real_money = round((floatval($withdraw_rate)+1)*floatval($val['money']),2);

        if(floatval($balance) < $real_money) {
            return ajax([],31);
        }

        Db::startTrans();
        try {
            $insert_data = [
                'order_sn' => create_unique_number('T'),
                'openid' => $openid,
                'money' => $val['money'],
                'withdraw_rate' => $withdraw_rate,
                'fee' => round(floatval($withdraw_rate)*floatval($val['money']),2),
                'real_money' => $real_money,
                'apply_time' => time(),
                'form_id' => $val['form_id']
            ];
            Db::table('mp_user')->where('openid','=',$openid)->setDec('balance',$real_money);
            Db::table('mp_withdraw')->insert($insert_data);
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            return ajax($e->getMessage(),-1);
        }
        $log = [
            'detail' => '申请提现',
            'money' => -$real_money,
            'type' => 3
        ];
        $this->billingLog($log);
        return ajax([],1);

    }











































    public function collectFormid() {
        $data['formid'] = input('post.formid');
        $res = Db::table('mp_formid')->insert($data);
        if($res) {
            return ajax([],1);
        }else {
            return ajax([],-1);
        }
    }

    public function test() {
    }









}
