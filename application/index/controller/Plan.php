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

    public function initialize()
    {
        parent::initialize();
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

    public function prizeStatus() {
        $_SERVER['REMOTE_ADDR'] = '47.104.130.39';
        if($_SERVER['REMOTE_ADDR'] === '47.104.130.39') {
            $detail = '';
            $map[] = ['open_time','<=',time()];
            $map[] = ['status','=',1];

            $exist = Db::table('mp_prize')->where($map)->select();
            if($exist) {
                //TODO 开奖活动
                $this->prizeResult($exist);
//                try {
//                    Db::table('mp_prize')->where($map)->update(['status'=>3]);
//                }catch (\Exception $e) {
//                    $detail = $e->getMessage();
//                }
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

    public function reqStatus() {
        if($_SERVER['REMOTE_ADDR'] === '47.104.130.39') {

            $data = [
                'detail'    =>  '',
                'cmd'   =>  request()->controller() . '/' . request()->action()
            ];
            Db::table('mp_planlog')->insert($data);
            echo 'curl success' . "\n";
        }else {
            echo 'denied access';
        }
    }

    public function wx_refund() {
        $app = Factory::payment($this->mp_config);
        $transactionId = '4200000183201810127612953670';
        $refundNumber = 'R153914887724190200';
        $totalFee = 1;
        $refundFee = 1;
        // 参数分别为：微信订单号、商户退款单号、订单金额、退款金额、其他参数
        $result = $app->refund->byTransactionId($transactionId,$refundNumber,$totalFee,$refundFee,$config = [
            'refund_desc' => '商品已售完123',
            'refund_account' => 'REFUND_SOURCE_RECHARGE_FUNDS'
            ]);
        halt($result);

    }

    public function transfer() {
        $app = Factory::payment($this->mp_config);
        $result  = $app->transfer->toBalance([
            'partner_trade_no' => '1235', // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => 'olIWK5ZRuUpmyxqiN4fNj_XxfszI',
            'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            're_user_name' => '姜海', // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
            'amount' => 100, // 企业付款金额，单位为分
            'desc' => '啊!订单无人接', // 企业付款操作说明信息。必填
        ]);
        halt($result);
    }


//选出中奖者和未中奖者
    private function prizeResult($exist = []) {
        foreach ($exist as $v) {
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
            Db::table('mp_prize_actor')->where('id','in',$winner)->update(['win'=>1]);
            Db::table('mp_prize_actor')->where('id','in',$loser)->update(['win'=>0]);
        }
    }
//Xt5b8FyGrHRGOLP1hLgtiVWCQrWT0DbI7CgU6k8Gvd0

    public function sendTpl() {
        $app = Factory::miniProgram($this->mp_config);
        $result = $app->template_message->send([
            'touser' => 'olIWK5ZRuUpmyxqiN4fNj_XxfszI',
            'template_id' => 'Xt5b8FyGrHRGOLP1hLgtiVWCQrWT0DbI7CgU6k8Gvd0',
            'page' => 'index',
            'form_id' => '1539337794436',
            'data' => [
                'keyword1' => '元旦送元宵',
                'keyword2' => '湾仔码头元宵',
                'keyword3' => '未中奖',
                'keyword4' => '近帮小程序',
            ]
        ]);
        halt($result);
    }













}