<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/12
 * Time: 12:23
 */
namespace app\index\controller;
use think\Controller;
use EasyWeChat\Factory;
use think\Db;

class Plan extends Controller {

    private $mp_config = [];
    private $weburl = '';

    public function initialize()
    {
        parent::initialize();
        $this->weburl = 'mp.jianghairui.com';
        $this->mp_config = [
            'app_id' => 'wx0d6f8a78265b1229',
            'secret' => 'b7cfdce371e0c100e7fc1d482933d7f5',

            'mch_id'             => '1514516351',
            'key'                => 'LDB15083727504163447056815279712',
            'cert_path'          =>  '/var/www/mp.jianghairui.com/public/cert/apiclient_cert.pem',
            'key_path'           =>  '/var/www/mp.jianghairui.com/public/cert/apiclient_key.pem',
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => APP_PATH . '/wechat.log',
            ],
        ];
    }
    //每天00:00分更新所有人抽奖次数为1
    public function luckyTimes() {
        if($_SERVER['REMOTE_ADDR'] === '47.104.130.39') {
            $detail = '';
            try {
                Db::table('mp_user')->where('1','=','1')->update(['times'=>1]);
            }catch (\Exception $e) {
                $detail = $e->getMessage();
            }
            $data = [
                'detail'    =>  $detail,
                'cmd'   =>  request()->controller() . '/' . request()->action()
            ];
            Db::table('mp_planlog')->insert($data);
            echo 'curl success' . "\n";
        }else {
            echo 'denied access';
        }
    }
    //执行的计划任务
    public function prizeStatus() {
        $_SERVER['REMOTE_ADDR'] = '47.104.130.39';
        if($_SERVER['REMOTE_ADDR'] === '47.104.130.39') {
            $map[] = ['open_time','<=',time()];
            $map[] = ['status','=',1];

            $exist = Db::table('mp_prize')->where($map)->select();
            if($exist) {
                //修改活动状态为已结束
                try {
                    Db::table('mp_prize')->where($map)->update(['status'=>3]);
                }catch (\Exception $e) {
                    $this->log('Plan/prizeResult',$e->getMessage());
                }
                foreach ($exist as $v) {    //选出中奖者和未中奖者
                    $probability = $v['probability']*100;
                    $prize_num = $v['num'];
                    $joiner = Db::table('mp_prize_actor')->where('prize_id','=',$v['id'])->column('id');
                    $winner = [];
                    $loser = [];
                    shuffle($joiner);
                    foreach ($joiner as $id) {
                        if($prize_num > 0) {
                            if(mt_rand(1,10000) <= $probability) {
                                $winner[] = $id;
                                $prize_num--;
                            }else {
                                $loser[] = $id;
                            }
                        }else {
                            $loser[] = $id;
                        }
                    }
                    try {
                        Db::table('mp_prize_actor')->where('id','in',$winner)->update(['win'=>1,'win_time'=>time()]);
                        Db::table('mp_prize_actor')->where('id','in',$loser)->update(['win'=>0]);
                    }catch (\Exception $e) {
                        $this->log('Plan/prizeResult',$e->getMessage());
                    }
                    $prize_list = Db::table('mp_prize_actor') ->where('prize_id','=',$v['id'])->select();
                    $redis = mredis(['select'=>1]);
                    foreach ($prize_list as $value) {
                        $redis->lPush('prizelist',[
                            'title' => $v['title'],
                            'prize' => $v['prize'],
                            'form_id' => $value['form_id'],
                            'openid' => $value['openid'],
                            'result' => $value['win']
                        ]);
                    }
                }
                $data = [
                    'detail'    =>  json_encode($exist),
                    'cmd'   =>  request()->controller() . '/' . request()->action()
                ];
                Db::table('mp_planlog')->insert($data);
            }

            echo 'curl success' . "\n";
        }else {
            echo 'denied access';
        }
    }
    //创建异步发送模板消息任务
    public function asyn_sendTpl() {
        if($_SERVER['REMOTE_ADDR'] === '47.104.130.39') {
            $redis = mredis(['select'=>1]);
            while (true) {
                $data = $redis->rPop('prizelist');
                if(!$data) {
                    break;
                }
                $param = http_build_query($data);
                $fp = fsockopen('ssl://' . $this->weburl, 443, $errno, $errstr, 20);
                if (!$fp){
                    echo 'error fsockopen';
                }else{
                    stream_set_blocking($fp,0);
                    $http = "GET /index/plan/sendTpl?".$param." HTTP/1.1\r\n";
                    $http .= "Host: ".$this->weburl."\r\n";
                    $http .= "Connection: Close\r\n\r\n";
                    fwrite($fp,$http);
                    usleep(1000);
                    fclose($fp);
                }
            }
        }

    }
    //发送模板消息
    public function sendTpl() {
        if($_SERVER['REMOTE_ADDR'] === '47.104.130.39') {
            $data = input('param.');
            $result = $data['result'] ? '已中奖' : '未中奖';
            $app = Factory::miniProgram($this->mp_config);
            $result = $app->template_message->send([
                'touser' => $data['openid'],
                'template_id' => 'Xt5b8FyGrHRGOLP1hLgtiVWCQrWT0DbI7CgU6k8Gvd0',
                'page' => 'index',
                'form_id' => $data['form_id'],
                'data' => [
                    'keyword1' => $data['title'],
                    'keyword2' => $data['prize'],
                    'keyword3' => $result,
                    'keyword4' => '近帮小程序',
                ]
            ]);
            //Db::table('mp_test')->insert(['cmd'=>'Plan/sendTpl','detail'=>json_encode($result)]);
        }
    }
    //执行的计划任务
    public function reqStatus() {
        $_SERVER['REMOTE_ADDR'] = '47.104.130.39';
        if($_SERVER['REMOTE_ADDR'] === '47.104.130.39') {
            $cmd = request()->controller() . '/' . request()->action();
            $map[] = ['pay_status','=',1];
            $map[] = ['status','in',[0,1]];
            $map[] = ['end_time','<=',time()];
            //订单到期没人接更改状态为无人接并进行退款
            $endtime_array = Db::table('mp_req')->where($map)->column('order_sn');
            if($endtime_array) {
                $log = [
                    'type' => 1,
                    'detail' => json_encode($endtime_array),
                    'cmd' => $cmd
                ];
                try {
                    Db::table('mp_planlog')->insert($log);
                    Db::table('mp_req')->where('order_sn','in',$endtime_array)->update(['status'=>-2]);//改为无人接状态
                }catch (\Exception $e) {
                    $this->log($cmd,$e->getMessage());
                }
                //todo 退款
                foreach ($endtime_array as $v) {
                    $arg['order_sn'] = $v;
                    $arg['reason'] = '订单无人接';
                    $this->asyn_refund($arg);
                }
            }
            //订单到期没提交更改状态为未完成并进行退款,并给予接单信誉值处罚
            $map2[] = ['pay_status','=',1];
            $map2[] = ['status','=',2];
            $map2[] = ['deadline','<=',time()];
            $deadline_array = Db::table('mp_req')->where($map2)->select();
            if($deadline_array) {
                $log = [
                    'type' => 2,
                    'detail' => json_encode($deadline_array),
                    'cmd' => $cmd
                ];
                try {
                    Db::table('mp_planlog')->insert($log);
                    Db::table('mp_req')->where($map2)->update(['status'=>5]);//改为未完成
                }catch (\Exception $e) {
                    $this->log($cmd,$e->getMessage());
                }
                //todo 退款
                $to_openid = [];
                foreach ($deadline_array as $v) {
                    $to_openid[] = $v['to_openid'];
                    $arg['order_sn'] = $v['order_sn'];
                    $arg['reason'] = '订单未完成';
                    $this->asyn_refund($arg);
                }
                //todo 减少信誉值
                $credit = Db::table('mp_setting')->where('id','=',1)->value('credit');
                try {
                    Db::table('mp_user')->where('openid','in',$to_openid)->setDec('credit',$credit);
                }catch (\Exception $e) {
                    $this->log($cmd,$e->getMessage());
                }
            }
            //订单按期提交,到期没确认(24小时后)更改状态为完成,并完成报酬的分配(接单人,分享人)
            $map3[] = ['pay_status','=',1];
            $map3[] = ['status','=',3];
            $map3[] = ['deadline','<=',time()-24*3600];
            $autoComplete = Db::table('mp_req')->where($map3)->select();
            if($autoComplete) {
                $log = [
                    'type' => 3,
                    'detail' => json_encode($autoComplete),
                    'cmd' => $cmd
                ];
                try {
                    Db::table('mp_planlog')->insert($log);
                    Db::table('mp_req')->where($map3)->update(['status'=>4]);//改为已完成
                }catch (\Exception $e) {
                    $this->log($cmd,$e->getMessage());
                }
                //todo 分配报酬(接单人,分享人)
                foreach ($autoComplete as $v) {
                    Db::startTrans();
                    try {
                        $reward = $v['order_price'] - $v['agency'];
                        Db::table('mp_user')->where('openid','=',$v['to_openid'])->setInc('balance',$reward);
                        Db::table('mp_user')->where('openid','=',$v['intro_openid'])->setInc('balance',$v['agency']);
                        Db::commit();
                    }catch (\Exception $e) {
                        Db::rollback();
                        $this->log($cmd,$e->getMessage());
                    }
                }
            }

            echo 'curl success' . "\n";
        }else {
            echo 'denied access';
        }
    }
    //创建异步退款任务
    private function asyn_refund($arg) {
        $data = [
            'order_sn' => $arg['order_sn'],
            'reason' => $arg['reason']
        ];
        $param = http_build_query($data);
        $fp = fsockopen('ssl://' . $this->weburl, 443, $errno, $errstr, 20);
        if (!$fp){
            echo 'error fsockopen';
        }else{
            stream_set_blocking($fp,0);
            $http = "GET /index/plan/wx_refund?".$param." HTTP/1.1\r\n";
            $http .= "Host: ".$this->weburl."\r\n";
            $http .= "Connection: Close\r\n\r\n";
            fwrite($fp,$http);
            usleep(1000);
            fclose($fp);
        }
    }
    //全额退款
    public function wx_refund() {
        if($_SERVER['REMOTE_ADDR'] === '47.104.130.39') {
            $cmd = request()->controller() . '/' . request()->action();
            $order_sn = input('param.order_sn');
            $reason = input('param.reason', '');
            $map[] = ['order_sn', '=', $order_sn];
            $map[] = ['pay_status', '=', 1];
            $map[] = ['status', 'in', [-1,-2,-3,5]];//未过审,无人接单,应邀人取消,未完成
            $exist = Db::table('mp_req')->where($map)->find();
            if ($exist) {
                try {
                    Db::table('mp_req')->where($map)->update(['pay_status' => 2]);//更改订单支付状态
                } catch (\Exception $e) {
                    $this->log($cmd, $e->getMessage());
                }
                //执行退款操作
                $app = Factory::payment($this->mp_config);
                $transactionId = $exist['trans_id'];
                $refundNumber = $order_sn;
                $totalFee = floatval($exist['real_price'])*100;
                $refundFee = floatval($exist['real_price'])*100;
                $config = [
                    'refund_desc' => $reason,
                ];
                // 参数分别为：微信订单号、商户退款单号、订单金额、退款金额、其他参数
                $result = $app->refund->byTransactionId($transactionId, $refundNumber, $totalFee, $refundFee, $config);
                if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
                    $refundlog = [
                        'order_sn' => $order_sn,
                        'trans_id' => $transactionId,
                        'detail' => json_encode($result),
                        'cmd' => $cmd,
                        'type' => 3,
                        'time' => time()
                    ];
                    Db::table('mp_req_refund')->insert($refundlog);
                } else if (isset($result['err_code']) && $result['err_code'] === 'NOTENOUGH') {
                    $config['refund_account'] = 'REFUND_SOURCE_RECHARGE_FUNDS';
                    $result = $app->refund->byTransactionId($transactionId, $refundNumber, $totalFee, $refundFee, $config);
                    $refundlog = [
                        'order_sn' => $order_sn,
                        'trans_id' => $transactionId,
                        'detail' => json_encode($result),
                        'cmd' => $cmd,
                        'type' => 4,
                        'time' => time()
                    ];
                    Db::table('mp_req_refund')->insert($refundlog);
                } else {
                    $refundlog = [
                        'order_sn' => $order_sn,
                        'trans_id' => $transactionId,
                        'detail' => json_encode($result),
                        'cmd' => $cmd,
                        'type' => 5,
                        'time' => time()
                    ];
                    Db::table('mp_req_refund')->insert($refundlog);
                }

            } else {
                $this->log($cmd, 'order_sn not exist:' . $order_sn);
            }
        }

    }
    //发布人取消订单退款(不退手续费)
    public function wx_cancel_refund() {
        if($_SERVER['REMOTE_ADDR'] === '47.104.130.39') {
            $cmd = request()->controller() . '/' . request()->action();
            $order_sn = input('param.order_sn');
            $reason = input('param.reason', '');
            $map[] = ['order_sn', '=', $order_sn];
            $map[] = ['pay_status', '=', 1];
            $map[] = ['status', '=', -3];//发布人取消,
            $exist = Db::table('mp_req')->where($map)->find();
            if ($exist) {
                try {
                    Db::table('mp_req')->where($map)->update(['pay_status' => 2]);//更改订单支付状态
                } catch (\Exception $e) {
                    $this->log($cmd, $e->getMessage());
                }
                //执行退款操作
                $app = Factory::payment($this->mp_config);
                $transactionId = $exist['trans_id'];
                $refundNumber = $order_sn;
                $totalFee = floatval($exist['real_price'])*100;
                $refundFee = floatval($exist['order_price'])*100;
                $config = [
                    'refund_desc' => $reason,
                ];
                // 参数分别为：微信订单号、商户退款单号、订单金额、退款金额、其他参数
                $result = $app->refund->byTransactionId($transactionId, $refundNumber, $totalFee, $refundFee, $config);
                if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
                    $refundlog = [
                        'order_sn' => $order_sn,
                        'trans_id' => $transactionId,
                        'detail' => json_encode($result),
                        'cmd' => $cmd,
                        'type' => 3,
                        'time' => time()
                    ];
                    Db::table('mp_req_refund')->insert($refundlog);
                } else if (isset($result['err_code']) && $result['err_code'] === 'NOTENOUGH') {
                    $config['refund_account'] = 'REFUND_SOURCE_RECHARGE_FUNDS';
                    $result = $app->refund->byTransactionId($transactionId, $refundNumber, $totalFee, $refundFee, $config);
                    $refundlog = [
                        'order_sn' => $order_sn,
                        'trans_id' => $transactionId,
                        'detail' => json_encode($result),
                        'cmd' => $cmd,
                        'type' => 4,
                        'time' => time()
                    ];
                    Db::table('mp_req_refund')->insert($refundlog);
                } else {
                    $refundlog = [
                        'order_sn' => $order_sn,
                        'trans_id' => $transactionId,
                        'detail' => json_encode($result),
                        'cmd' => $cmd,
                        'type' => 5,
                        'time' => time()
                    ];
                    Db::table('mp_req_refund')->insert($refundlog);
                }

            } else {
                $this->log($cmd, 'order_sn not exist:' . $order_sn);
            }
        }

    }
    //订单最终矛盾退款
    public function wx_dispute_refund() {
        if($_SERVER['REMOTE_ADDR'] === '47.104.130.39') {
            $cmd = request()->controller() . '/' . request()->action();
            $order_sn = input('param.order_sn');
            $reason = input('param.reason', '订单退款');
            $refund = input('param.refund');
            $map[] = ['order_sn', '=', $order_sn];
            $map[] = ['pay_status', '=', 1];
            $map[] = ['status', '=', 6];
            $exist = Db::table('mp_req')->where($map)->find();
            if ($exist) {
                try {
                    Db::table('mp_req')->where($map)->update(['pay_status' => 2]);//更改订单支付状态
                } catch (\Exception $e) {
                    $this->log($cmd, $e->getMessage());
                }
                //执行退款操作
                $app = Factory::payment($this->mp_config);
                $transactionId = $exist['trans_id'];
                $refundNumber = $order_sn;
                $totalFee = floatval($exist['real_price'])*100;
                $refundFee = floatval($refund)*100;
                $config = [
                    'refund_desc' => $reason,
                ];
                // 参数分别为：微信订单号、商户退款单号、订单金额、退款金额、其他参数
                $result = $app->refund->byTransactionId($transactionId, $refundNumber, $totalFee, $refundFee, $config);
                if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
                    $refundlog = [
                        'order_sn' => $order_sn,
                        'trans_id' => $transactionId,
                        'detail' => json_encode($result),
                        'cmd' => $cmd,
                        'type' => 3,
                        'time' => time()
                    ];
                    Db::table('mp_req_refund')->insert($refundlog);
                } else if (isset($result['err_code']) && $result['err_code'] === 'NOTENOUGH') {
                    $config['refund_account'] = 'REFUND_SOURCE_RECHARGE_FUNDS';
                    $result = $app->refund->byTransactionId($transactionId, $refundNumber, $totalFee, $refundFee, $config);
                    $refundlog = [
                        'order_sn' => $order_sn,
                        'trans_id' => $transactionId,
                        'detail' => json_encode($result),
                        'cmd' => $cmd,
                        'type' => 4,
                        'time' => time()
                    ];
                    Db::table('mp_req_refund')->insert($refundlog);
                } else {
                    $refundlog = [
                        'order_sn' => $order_sn,
                        'trans_id' => $transactionId,
                        'detail' => json_encode($result),
                        'cmd' => $cmd,
                        'type' => 5,
                        'time' => time()
                    ];
                    Db::table('mp_req_refund')->insert($refundlog);
                }

            } else {
                $this->log($cmd, 'order_sn not exist:' . $order_sn);
            }
        }
    }



    protected function log($cmd,$str) {
        $file= ROOT_PATH . '/exception_plan.txt';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
        die();
    }

/***********以下为调试用接口**************/
    private function refund() {
            $app = Factory::payment($this->mp_config);
            $transactionId = '';
            $refundNumber = 'R153916990348509600';
            $totalFee = 1;
            $refundFee = 1;
            // 参数分别为：微信订单号、商户退款单号、订单金额、退款金额、其他参数
            $config = [
                'refund_desc' => '手动退款',
            ];
            $result = $app->refund->byTransactionId($transactionId,$refundNumber,$totalFee,$refundFee,$config);
            halt($result);
    }








}