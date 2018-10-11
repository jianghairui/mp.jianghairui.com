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

        $val['order_sn'] = input('post.order_sn');
        $val['order_sn'] = 'R153898403430036900';
        $this->checkPost($val);
        $map[] = ['order_sn','=',$val['order_sn']];
        $map[] = ['pay_status','=',0];
        $exist = Db::table('mp_req')->where($map)->find();
        if(!$exist || $exist['f_openid'] != $this->myinfo['openid']) {
            return ajax('订单不存在',21);
        }

        $app = Factory::payment($this->mp_config);
        try {
            $result = $app->order->unify([
                'body' => $exist['title'],
                'out_trade_no' => $val['order_sn'],
//                'total_fee' => floatval($exist['real_price']) * 100,
                'total_fee' => 1,

                'notify_url' => $this->domain . 'index/pay/notify',
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
    //支付回调接口
    public function notify() {
        //将返回的XML格式的参数转换成php数组格式
        $xml = file_get_contents('php://input');
        $data = $this->xml2array($xml);

        if($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
            $map = [
                ['order_sn','=',$data['out_trade_no']],
                ['pay_status','=',0],
            ];
            $exist = Db::table('mp_req')->where($map)->find();
            if($exist) {
                $update_data = [
                    'pay_status' => 1,
                    'trans_id' => $data['transaction_id'],
                    'pay_time' => time(),
                ];
                Db::table('mp_req')->where('order_sn','=',$data['out_trade_no'])->update($update_data);
            }

        }else if($data['return_code'] == 'SUCCESS' && $data['result_code'] != 'SUCCESS'){
            $data['out_trade_no'] = '支付失败';
        }

        $order_sn = isset($data['out_trade_no']) ? $data['out_trade_no'] : '';
        Db::table('mp_paylog')->insert(['order_sn'=>$order_sn,'detail'=>json_encode($data)]);
        echo $this->array2xml(['return_code'=>'SUCCESS','return_msg'=>'OK']);

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