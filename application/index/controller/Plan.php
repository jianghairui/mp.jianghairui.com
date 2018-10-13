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
            // 下面为可选项,指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
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