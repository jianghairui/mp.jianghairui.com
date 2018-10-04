<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/4
 * Time: 10:50
 */
namespace app\index\controller;
use EasyWeChat\Factory;
use think\Db;
use think\Exception;

class Pay extends Common {

    public function pay() {
        $app = Factory::payment($this->mp_config);
        try {
            $result = $app->order->unify([
                'body' => '近帮需求小程序支付',
                'out_trade_no' => time(),
                'total_fee' => 1,
                'notify_url' => 'https://mp.jianghairui.com/index/pay/notify',
                'trade_type' => 'JSAPI',
                'openid' => $this->myinfo['openid'],
            ]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
            return ajax($result['err_code_des'],-1);
        }

        try {
            $result['timestamp'] = strval(time());
            $sign['appId'] = $result['appid'];
            $sign['timeStamp'] = $result['timestamp'];
            $sign['nonceStr'] = $result['nonce_str'];
            $sign['signType'] = 'MD5';
            $sign['package'] = 'prepay_id=' . $result['prepay_id'];
            $result['paySign'] = $this->getSign($sign);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($result);
    }

    public function notify() {

        $xml = file_get_contents("php://input");
        $result = $this->xml2array($xml);
        if($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
            $file= ROOT_PATH . '/notify.txt';
            $text='记录时间 ---' . date('Y-m-d H:i:s') . "\n" . var_export($result,true) . '---END---' . "\n";
            if(false !== fopen($file,'a+')){
                file_put_contents($file,$text,FILE_APPEND);
            }else{
                echo '创建失败';
            }
        }
        Db::table('mp_paylog')->insert(
            ['detail' => json_encode($result),'type'=>1]
        );
    }


    //生成签名
    private function getSign($arr)
    {
        //去除数组中的空值
        $arr = array_filter($arr);
        //如果数组中有签名删除签名
        if(isset($arr['sing']))
        {
            unset($arr['sing']);
        }
        //按照键名字典排序
        ksort($arr);
        //生成URL格式的字符串
        $str = http_build_query($arr)."&key=".$this->mp_config['key'];
        $str = $this->arrToUrl($str);
        return  strtoupper(md5($str));
    }
    //URL解码为中文
    private function arrToUrl($str)
    {
        return urldecode($str);
    }

}