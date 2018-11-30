<?php
namespace app\index\controller;
use EasyWeChat\Factory;
use function GuzzleHttp\default_ca_bundle;
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
    //获取职业列表
    public function getResumeList() {
        $where[] = ['status','=',1];
        $list = Db::table('mp_resume')->where($where)->select();
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
    //获取可充值类目列表
    public function getVipList() {
        $list = Db::table('mp_vip')->select();
        return ajax($list);
    }
    //选择充值类目
    public function recharge() {
        $vip_id = input('post.vip_id');
        $exist = Db::table('mp_vip')->where('id','=',$vip_id)->find();
        if(!$exist) {
            return ajax([],-3);
        }
        $insert = [
            'order_sn' => create_unique_number('V'),
            'vip_id' => $vip_id,
            'order_price' => $exist['price'],
            'openid' => $this->myinfo['openid'],
            'create_time' => time(),
            'days' => $exist['days']
        ];
        try {
            Db::table('mp_vip_pay')->insert($insert);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $app = Factory::payment($this->mp_config);
        $result = $app->order->unify([
            'body' => $exist['title'],
            'out_trade_no' => $insert['order_sn'],
//                'total_fee' => floatval($exist['real_price']) * 100,
            'total_fee' => 1,
            'notify_url' => $this->domain . 'index/pay/rechargeNotify',
            'trade_type' => 'JSAPI',
            'openid' => $this->myinfo['openid'],
        ]);
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
    //我正在申请的列表
    public function myApplying() {
        //todo 暂时不写
    }
    //我申请成功的列表
    public function myApplyList() {
        $map[] = ['r.to_openid','=',$this->myinfo['openid']];
        $page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $data['count'] = Db::table('mp_req')->alias('r')->where($map)->count();
        $data['list'] = Db::table('mp_req')
            ->alias('r')
            ->join('mp_cate c','r.cate_id=c.id','left')
            ->field('r.title,r.content,r.address,r.num,r.order_price,r.real_price,r.create_time,r.status,c.cate_name')
            ->where($map)
            ->order(['r.id'=>'DESC'])->limit(($page-1)*$perpage,$perpage)->select();
        return ajax($data);
    }

    //我的发布列表            SELECT * FROM article ORDER BY LOCATE(userid,'4,1,2,3'),id DESC;
    public function myRelease() {
        $map[] = ['r.f_openid','=',$this->myinfo['openid']];
        $req_status = input('post.status');

        if(isset($req_status) && $req_status !== '') {
            $map[] = ['r.status','=',$req_status];
        }
        $page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $data['count'] = Db::table('mp_req')->alias('r')->where($map)->count();
        $data['list'] = Db::table('mp_req')->alias('r')
            ->join('mp_cate c','r.cate_id=c.id','left')
            ->where($map)
            ->field('r.order_sn,r.title,r.content,r.address,r.num,r.order_price,r.real_price,r.create_time,r.pay_status,r.status,c.cate_name')
            ->order(['r.id'=>'DESC'])->limit(($page-1)*$perpage,$perpage)->select();
        return ajax($data);
    }
    //接单的候选者列表
    public function myChoiceList() {
        $map[] = ['status','=',1];
        $map[] = ['f_openid','=',$this->myinfo['openid']];
        $rid = Db::table('mp_req')->where($map)->column('id');
        $list = Db::table('mp_apply')->alias('a')
            ->join('mp_req r','a.rid=r.id','left')
            ->field('a.id,a.to_avatar,a.to_nickname,a.apply_time,r.title')
            ->where('rid','in',$rid)->select();
        return ajax($list,1);
    }
    //查看候选者详情
    public function getApplyDetail() {
        $apply_id = input('post.apply_id');
        $exist = Db::table('mp_apply')->where('id','=',$apply_id)->find();
        if(!$exist) {
            return ajax([],-3);
        }
        $userinfo = Db::table('mp_user')->alias('u')
            ->join('mp_userinfo i','u.openid=i.openid','left')
            ->where('u.openid','=',$exist['to_openid'])
            ->field('u.nickname,u.avatar,u.credit,u.gender,u.age,u.tel,u.city,i.job,i.resume,i.career')
            ->find();
        return ajax($userinfo);
    }
    //选择一个接单人
    public function makeChoice() {
        $apply_id = input('post.apply_id');
        if(!$apply_id) {
            return ajax(['apply_id'=>$apply_id],-2);
        }
        $exist = Db::table('mp_apply')->where('id','=',$apply_id)->find();
        if(!$exist) {
            return ajax([],-3);
        }
        $map = [
            ['id','=',$exist['rid']],
            ['status','=',1],
            ['f_openid','=',$this->myinfo['openid']]
        ];
        $req = Db::table('mp_req')->where($map)->find();
        if(!$req) {
            return ajax([],10);
        }

        $update_data = [
            'to_openid' => $exist['to_openid'],
            'status' => 2,
            'take_time' => time()
        ];
        if($exist['intro_openid']) {
            $agency_rate = Db::table('mp_setting')->where('id','=',1)->value('agency_rate');
            $agency = $agency_rate*$req['order_price'];
            $update_data['intro_openid'] = $exist['intro_openid'];
            $update_data['agency'] = $agency;
        }
        Db::startTrans();
        try {
            Db::table('mp_apply')->where('id','=',$apply_id)->update(['status'=>1]);
            Db::table('mp_apply')->where([
                ['id','<>',$apply_id],
                ['rid','=',$exist['rid']]
            ])->update(['status'=>-1]);
            Db::table('mp_req')->where($map)->update($update_data);
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            return ajax($e->getMessage(),-1);
        }
        //todo 给接单人发一个通知(暂定)
        return ajax([],1);
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
        $this->checkRealnameAuth();
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

        $real_money = floor((floatval($withdraw_rate)+1)*floatval($val['money'])*100)/100;
        $fee = bcsub($real_money , floatval($val['money']) ,2);

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
                'fee' => $fee,
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
            'type' => 3,
            'openid' => $this->myinfo['openid'],
        ];
        $this->billingLog($log);
        return ajax([],1);

    }
    //获取抽奖纪录
    public function luckDrawList() {
        $page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $where[] = ['a.openid','=',$this->myinfo['openid']];
        $data['count'] = Db::table('mp_prize_actor')->alias('a')->where($where)->count();
        $data['list'] = Db::table('mp_prize_actor')->alias('a')
            ->join('mp_prize p','a.prize_id=p.id','left')
            ->where($where)
            ->field('a.order_sn,a.win,p.title,p.prize,p.cover,p.open_time,p.status')
            ->limit(($page-1)*$perpage,$perpage)
            ->select();
        return ajax($data);

    }
    //中奖详情
    public function luckyDrawDetail() {
        $order_sn = input('post.order_sn');
        if(!$order_sn) {
            return ajax([],-2);
        }
        $where = [
            ['a.order_sn','=',$order_sn],
            ['a.openid','=',$this->myinfo['openid']]
        ];
        $exist = Db::table('mp_prize_actor')->alias('a')
            ->join('mp_prize p','a.prize_id=p.id','left')
            ->where($where)
            ->field('a.prize_id,a.order_sn,a.win,a.price,p.title,p.prize,p.cover,p.open_time,p.status')
            ->find();
        if(!$exist) {
            return ajax([],10);
        }
        return ajax($exist);

    }
    //填写手机号地址并支付
    public function payForPrize() {
        $val['order_sn'] = input('post.order_sn');
        $val['tel'] = input('post.tel');
        $val['address'] = input('post.address');
        $this->checkPost($val);

        if(!is_tel($val['tel'])) {
            return ajax([],14);
        }

        $where = [
            ['order_sn','=',$val['order_sn']],
            ['win','=',1],
            ['openid','=',$this->myinfo['openid']]
        ];
        $exist = Db::table('mp_prize_actor')
            ->where($where)
            ->field('order_sn,price')
            ->find();
        if(!$exist) {
            return ajax([],10);
        }
        try {
            Db::table('mp_prize_actor')->where($where)->update($val);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        //发起订单
        $app = Factory::payment($this->mp_config);
        try {
            $result = $app->order->unify([
                'body' => '近帮小程序奖品运费',
                'out_trade_no' => $val['order_sn'],
//                'total_fee' => floatval($exist['price']) * 100,
                'total_fee' => 1,
                'notify_url' => $this->domain . 'index/index/prizeNotify',
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

    public function prizeNotify() {
        $xml = file_get_contents('php://input');
        $data = $this->xml2array($xml);
        if($data) {
            if($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                $map = [
                    ['order_sn','=',$data['out_trade_no']],
                    ['pay_status','=',0],
                ];
                $exist = Db::table('mp_prize_actor')->where($map)->find();
                if($exist) {
                    $update_data = [
                        'pay_status' => 1,
                        'trans_id' => $data['transaction_id'],
                        'pay_time' => time(),
                    ];
                    try {
                        Db::table('mp_prize_actor')->where('order_sn','=',$data['out_trade_no'])->update($update_data);
                    } catch (\Exception $e) {
                        $this->log('notify',$e->getMessage());
                    }
                }

            }else if($data['return_code'] == 'SUCCESS' && $data['result_code'] != 'SUCCESS'){
                $data['out_trade_no'] = '支付失败';
            }
            try {
                $order_sn = isset($data['out_trade_no']) ? $data['out_trade_no'] : '';
                Db::table('mp_paylog')->insert(['order_sn'=>$order_sn,'detail'=>json_encode($data),'type'=>3]);
            }catch (\Exception $e) {
                $this->log('notify',$e->getMessage());
            }
        }
        exit($this->array2xml(['return_code'=>'SUCCESS','return_msg'=>'OK']));
    }
    //接单人提交完成,更改订单状态
    public function submitJob() {

        $val['req_id'] = input('post.req_id');
        $val['detail'] = input('post.detail');
        $image = input('post.image');
        $this->checkPost($val);
        $map = [
            ['id','=',$val['req_id']],
            ['status','=',2]
        ];
        $exist = Db::table('mp_req')->where($map)->find();
        if(!$exist || $exist['to_openid'] != $this->myinfo['openid']) {
            return ajax([],10);
        }
        if(is_array($image) && !empty($image)) {
            if(count($image) > 9) {
                return ajax('最多上传9张图片',9);
            }
            foreach ($image as $v) {
                if(!file_exists($v)) {
                    return ajax($v,29);
                }
            }
        }
        $image_array = [];
        foreach ($image as $v) {
            $image_array[] = $this->rename_file($v);
        }
        $val['image'] = serialize($image_array);
        Db::startTrans();
        try {
            Db::table('mp_req_progress')->insert($val);
            $update_data = [
                'status' => 3,
                'submit_time' => time()
            ];
            Db::table('mp_req')->where($map)->update($update_data);
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            return ajax($e->getMessage(),-1);
        }
        return ajax();

    }
    //应邀人取消订单,更改订单状态
    public function pickerCancel() {
        //todo 10分钟内取消 扣除信誉值,给接单人退款.10分钟后无法取消
        $val['req_id'] = input('post.req_id');
        $this->checkPost($val);
        $map = [
            ['id','=',$val['req_id']],
            ['status','in',[2,3]]
        ];
        $exist = Db::table('mp_req')->where($map)->find();
        if(!$exist || $exist['to_openid'] != $this->myinfo['openid']) {
            return ajax([],10);
        }
        switch ($exist['status']) {
            case 3:
                return ajax([],33);break;
            default:
                if((time() - $exist['take_time']) > 10*60) {
                    return ajax([],35);
                }
                //todo 给发布人退全款
                Db::startTrans();
                try {
                    $update_req = [
                        'cancel_time' => time(),
                        'status' => -3
                    ];
                    $setting = Db::table('mp_setting')->where('id','=',1)->find();
                    $user = Db::table('mp_user')->where('to_openid','=',$this->myinfo['openid'])->find();
                    if(($user['credit'] - $setting['credit']) <= $setting['min_credit']) {
                        $update_user['status'] = 2;
                    }
                    $update_user['credit'] = $user['credit'] - $setting['credit'];
                    Db::table('mp_req')->where($map)->update($update_req);
                    Db::table('mp_user')->where('to_openid','=',$this->myinfo['openid'])->update($update_user);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    return ajax($e->getMessage(),-1);
                }
                $arg = [
                    'order_sn' => $exist['order_sn'],
                    'reason' => '订单被取消'
                ];
                $this->asyn_refund($arg);
                return ajax();
        }


    }
    //发单人取消订单,更改订单状态
    public function issuerCancel() {
        $val['req_id'] = input('post.req_id');
        $this->checkPost($val);
        $map = [
            ['id','=',$val['req_id']],
            ['status','in',[0,1,2,3]]
        ];
        $exist = Db::table('mp_req')->where($map)->find();
        if(!$exist || $exist['f_openid'] != $this->myinfo['openid']) {
            return ajax([],10);
        }
        if(in_array($exist['status'],[0,1])) {
            //todo 订单审核中、待接单状态直接取消,退全款
            return ajax();
        }
        switch ($exist['status']) {
            case 3:
                return ajax([],33);break;
            default:
                //订单超过10分钟无法退款
                if((time() - $exist['take_time']) > 10*60) {
                    return ajax([],35);
                }
                //todo 订单已接单,退款,不退手续费
                try {
                    $update_req = [
                        'cancel_time' => time(),
                        'status' => -3
                    ];
                    Db::table('mp_req')->where($map)->update($update_req);
                } catch (\Exception $e) {
                    return ajax($e->getMessage(),-1);
                }
                $arg = [
                    'order_sn' => $exist['order_sn'],
                    'reason' => '订单被取消'
                ];
                $this->asyn_refund($arg,1);
                return ajax();
        }

    }
    //发布人确认完成订单
    public function orderConfirm() {
        $val['req_id'] = input('post.req_id');
        $this->checkPost($val);
        $map = [
            ['pay_status','=',1],
            ['status','in',[2,3]],
            ['id','=',$val['req_id']],
            ['f_openid','=',$this->myinfo['openid']]
        ];
        $exist = Db::table('mp_req')->where($map)->find();
        if(!$exist) {
            return ajax([],10);
        }

        Db::startTrans();
        try {
            $reward = $exist['order_price'] - $exist['agency'];
            $picker_billing = [
                'req_id' => $val['req_id'],
                'detail' => '收入',
                'money' => $reward,
                'openid' => $this->myinfo['openid'],
                'type' => 1
            ];
            Db::table('mp_req')->where($map)->update(['status'=>4]);//更改订单状态
            $this->billingLog($picker_billing);
            Db::table('mp_user')->where('openid','=',$exist['to_openid'])->setInc('balance',$reward);
            if($exist['intro_openid']) {
                $agency_billing = [
                    'req_id' => $val['req_id'],
                    'detail' => '分享提成',
                    'money' => $exist['agency'],
                    'openid' => $exist['intro_openid'],
                    'type' => 2
                ];
                $this->billingLog($agency_billing);
                Db::table('mp_user')->where('openid','=',$exist['intro_openid'])->setInc('balance',$exist['agency']);
            }
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

//创建异步退款任务
    private function asyn_refund($arg,$type=0) {
        $data = [
            'order_sn' => $arg['order_sn'],
            'reason' => $arg['reason']
        ];
        $cmd = $type ? 'wx_cancel_refund' : 'wx_refund';
        $param = http_build_query($data);
        $fp = fsockopen('ssl://' . $this->weburl, 443, $errno, $errstr, 20);
        if (!$fp){
            echo 'error fsockopen';
        }else{
            stream_set_blocking($fp,0);
            $http = "GET /index/plan/".$cmd."?".$param." HTTP/1.1\r\n";
            $http .= "Host: ".$this->weburl."\r\n";
            $http .= "Connection: Close\r\n\r\n";
            fwrite($fp,$http);
            usleep(1000);
            fclose($fp);
        }
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








}
