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
                'total_fee' => floatval($exist['real_price']) * 100,
//                'total_fee' => 1,
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
    //发送消息模板
    public function sendTpl() {
        $val['prepay_id'] = input('post.prepay_id');
        $val['order_sn'] = input('post.order_sn');
        $this->checkPost($val);
        $map[] = ['order_sn','=',$val['order_sn']];
        $map[] = ['f_openid','=',$this->myinfo['openid']];
        $order = Db::table('mp_req')->where($map)->find();
        if(!$order) {
            return ajax([],10);
        }
        $app = Factory::miniProgram($this->mp_config);
        $result = $app->template_message->send([
            'touser' => $this->myinfo['openid'],
            'template_id' => 'AF7VT4VdewpEJuRYiuKOPTp8ckSZOzicfwbawOXdX5I',
            'page' => 'index',
            'form_id' => strval($val['prepay_id']),
            'data' => [
                'keyword1' => $order['title'],
                'keyword2' => $order['real_price'],
                'keyword3' => date('Y-m-d H:i:s'),
                'keyword4' => $order['order_sn'],
            ]
        ]);
        return ajax($result);
    }
    //支付回调接口
    public function notify() {
        //将返回的XML格式的参数转换成php数组格式
        $xml = file_get_contents('php://input');
        $data = $this->xml2array($xml);
        if($data) {
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
                    try {
                        Db::table('mp_req')->where('order_sn','=',$data['out_trade_no'])->update($update_data);
                    } catch (\Exception $e) {
                        $this->log('notify',$e->getMessage());
                    }
                }

            }else if($data['return_code'] == 'SUCCESS' && $data['result_code'] != 'SUCCESS'){
                $data['out_trade_no'] = '支付失败';
            }
            try {
                $order_sn = isset($data['out_trade_no']) ? $data['out_trade_no'] : '';
                Db::table('mp_paylog')->insert(['order_sn'=>$order_sn,'detail'=>json_encode($data),'type'=>1]);
            }catch (\Exception $e) {
                $this->log('notify',$e->getMessage());
            }
        }
        exit($this->array2xml(['return_code'=>'SUCCESS','return_msg'=>'OK']));

    }
    //充值VIP支付回调接口
    public function rechargeNotify() {
        $xml = file_get_contents('php://input');
        $data = $this->xml2array($xml);
        if($data) {
            if($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                $map = [
                    ['order_sn','=',$data['out_trade_no']],
                    ['status','=',0],
                ];
                $exist = Db::table('mp_vip_pay')->where($map)->find();
                if($exist) {
                    $user = Db::table('mp_user')->where('openid','=',$exist['openid'])->find();
                    $update_data = [
                        'status' => 1,
                        'trans_id' => $data['transaction_id'],
                        'pay_time' => time(),
                    ];
                    try {
                        Db::table('mp_vip_pay')->where('order_sn','=',$data['out_trade_no'])->update($update_data);
                        if($user['vip'] == 1) {
                            $update = [
                                'vip' => 1,
                                'vip_time' => $user['vip_time'] + $exist['days']*3600*24
                            ];
                        }else {
                            $update = [
                                'vip' => 1,
                                'vip_time' => time() + $exist['days']*3600*24
                            ];
                        }
                        Db::table('mp_user')->where('openid','=',$exist['openid'])->update($update);
                    }catch (\Exception $e) {
                        $this->log('rechargeNotify',$e->getMessage());
                    }
                }

            }else if($data['return_code'] == 'SUCCESS' && $data['result_code'] != 'SUCCESS'){
                $data['out_trade_no'] = '支付失败';
            }
            try {
                $order_sn = isset($data['out_trade_no']) ? $data['out_trade_no'] : '';
                Db::table('mp_paylog')->insert(['order_sn'=>$order_sn,'detail'=>json_encode($data),'type'=>2]);
            }catch (\Exception $e) {
                $this->log('rechargeNotify',$e->getMessage());
            }
        }
        exit($this->array2xml(['return_code'=>'SUCCESS','return_msg'=>'OK']));
    }






}