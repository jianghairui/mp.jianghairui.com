<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/20
 * Time: 16:36
 */
namespace app\index\controller;
use EasyWeChat\Factory;
use think\Db;
use think\Exception;

class Login extends Common {

    //小程序登录
    public function login()
    {
        $code = input('post.code');
        $this->checkPost(['code'=>$code]);
        $app = Factory::miniProgram($this->mp_config);
        $info = $app->auth->session($code);

        if(isset($info['errcode']) && $info['errcode'] !== 0) {
            return ajax($info,-1);
        }
        $ret['openid'] = $info['openid'];
        $ret['session_key'] = $info['session_key'];

        $exist = Db::table('mp_user')->where('openid',$ret['openid'])->find();
        if($exist) {
            Db::table('mp_user')->where('openid',$ret['openid'])->update(['last_login_time'=>time()]);
        }
        //把3rd_session存入redis
        $third_session = exec('/usr/bin/head -n 80 /dev/urandom | tr -dc A-Za-z0-9 | head -c 168');
        mredis()->set($third_session,$ret, 3600*24*7);

        $json['third_session'] = $third_session;
        return ajax($json);
    }
    //小程序授权
    public function auth() {
        $iv = input('post.iv');
        $encryptData = input('post.encryptData');
        $this->checkPost([
            'iv' => $iv,
            'encryptData' => $encryptData
        ]);
        if(!$iv || !$encryptData) {
            return ajax([],-2);
        }
        $app = Factory::miniProgram($this->mp_config);
        try {
            $decryptedData = $app->encryptor->decryptData($this->myinfo['session_key'], $iv, $encryptData);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),4);
        }

        try {
            $data['nickname'] = $decryptedData['nickName'];
            $data['openid'] = $decryptedData['openId'];
            $data['avatar'] = $decryptedData['avatarUrl'];
            $data['gender'] = $decryptedData['gender'];
            $data['city'] = $decryptedData['city'];
            $data['country'] = $decryptedData['country'];
            $data['province'] = $decryptedData['province'];
            $data['status'] = 1;
            $data['create_time'] = time();
            $exist = Db::table('mp_user')->where('openid',$data['openid'])->find();
            if(!$exist) {
                Db::table('mp_user')->insert($data);
            }else {
                Db::table('mp_user')->where('openid',$data['openid'])->update($data);
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),4);
        }
        return ajax('授权成功');
    }

//检验地区是否开放
    private function checkCity($long = '117.04724',$lat = '39.06455') {
        $info = \my\Geocoding::getAddressComponent($long,$lat);
        $city = $info['result']['addressComponent']['city'];
        $exist = Db::table('mp_city')->where(['name'=>$city,'pid'=>0])->find();
        if($exist) {
            return true;
        }else {
            return false;
        }
    }


}