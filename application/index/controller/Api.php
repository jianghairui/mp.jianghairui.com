<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/8
 * Time: 11:11
 */
namespace app\index\controller;
use EasyWeChat\Factory;
use think\Db;
use think\Exception;

class Api extends Common {

    //获取城市列表
    public function getCitylist() {
        $citylist = Db::table('mp_city')->select();
        $data['list'] = $this->sortMerge($citylist,0);
        return ajax($data);
    }
    //获取需求列表
    public function getRlist()
    {
        $condition['page'] = input('post.page',2);
        $condition['perpage'] = input('post.perpage',10);
        $condition['city'] = input('post.city');
        $condition['county'] = input('post.county');
        $condition['lon'] = input('post.lon');
        $condition['lat'] = input('post.lat');

        $model = model('Req');
        $list = $model::sortlist($condition);
        return ajax($list,1);
    }
    //获取分类列表
    public function getCatelist() {
        $map[] = ['status','=',1];
        $catelist = Db::table('mp_cate')->where($map)->select();
        $data['list'] = $this->sortMerge($catelist,0);
        return ajax($data);
    }
    //获取系统参数
    public function getSettings() {
        $info = Db::table('mp_setting')->find();
        return ajax($info,1);
    }
    //发布需求
    public function requireRelease ()
    {
        $val['title'] = input('post.title');
        $val['content'] = input('post.content');
        $val['order_price'] = input('post.price');
        $val['rate'] = Db::table('mp_setting')->value('req_rate');
        $val['address'] = input('post.address');
        $val['lon'] = input('post.lon');
        $val['lat'] = input('post.lat');
        $val['num'] = input('post.num');
        $val['cate_id'] = input('post.cate_id');
        $val['end_time'] = input('post.end_time');
        $val['deadline'] = input('post.deadline');

        $this->checkPost($val);
        $this->checkRealnameAuth();

        if(!$this->checkExist('mp_cate',[
            ['id','=',$val['cate_id']],
            ['pid','<>',0]
            ])) {
            return ajax([],-3);
        }

        if(!is_currency($val['order_price'])) {
            return ajax([],5);
        }

        if(!is_lonlat($val['lon'],$val['lat'])) {
            return ajax([],6);
        }

        if(!preg_match("/^[0-9]{1,8}$/",$val['num'])) {
            return ajax([],7);
        }

        if(!is_date($val['end_time']) || !is_date($val['deadline'])) {
            return ajax([],8);
        }

        $val['order_sn'] = create_unique_number('R');
        $val['real_price'] = $val['order_price'] * (1+$val['rate']);
        $val['f_openid'] = $this->myinfo['openid'];
        $val['fee'] = $val['order_price'] * $val['rate'];
        $val['create_time'] = time();
        $val['end_time'] = strtotime($val['end_time']);
        $val['deadline'] = strtotime($val['deadline']);

        $cityinfo = $this->getCityinfo($val['lon'],$val['lat']);

        $val['province'] = $cityinfo['province'];
        $val['city'] = $cityinfo['city'];
        $val['county'] = $cityinfo['district'];

        foreach ($_FILES as $k=>$v) {
            if($v['name'] == '') {
                unset($_FILES[$k]);
            }
        }
        if(count($_FILES) >= 9) {
            return ajax('最多上传9张图片',9);
        }

        $info = $this->multi_upload('static/uploads/req/');
        if($info['error'] === 0) {
            $val['image'] = serialize($info['data']);
        }else {
            return ajax($info['msg'],9);
        }
        try {
            Db::table('mp_req')->insert($val);
        }catch (\Exception $e) {
            if(count($_FILES) > 0) {
                foreach ($info['data'] as $v) {
                    @unlink($v);
                }
            }
            return ajax($e->getMessage(),-1);
        }
        return ajax($val,1);

    }
    //申请需求
    public function apply()
    {
        $val['rid'] = input('post.rid');
        $this->checkPost($val);
        $val['intro_openid'] = input('post.intro_openid');
        $val['to_openid'] = $this->myinfo['openid'];

        $this->checkRealnameAuth();

        if($val['intro_openid'] == $this->myinfo['openid']) {
            unset($val['intro_openid']);
        }

        if($val['intro_openid']) {
            $res = Db::table('mp_user')->where('openid','=',$val['intro_openid'])->find();
            if(!$res) {
                return ajax('分享人ID不存在',23);
            }
        }

        $map[] = ['id','=',$val['rid']];
        $map[] = ['status','=',1];

        $req_exist = Db::table('mp_req')->where($map)->find();
        if(!$req_exist) {
            return ajax([],10);
        }

        if($req_exist['f_openid'] == $this->myinfo['openid']) {
            return ajax('这是自己发的单',22);
        }

        $where[] = ['rid','=',$val['rid']];
        $where[] = ['to_openid','=',$val['to_openid']];
        if($this->checkExist('mp_apply',$where)) {
            return ajax([],11);
        }

        $val['apply_time'] = time();
        $user = Db::table('mp_user')->where('openid','=',$this->myinfo['openid'])->find();
        $val['to_nickname'] = $user['nickname'];
        $val['to_avatar'] = $user['avatar'];

        try {
            $res = Db::table('mp_apply')->insert($val);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //获取轮播图列表
    public function slideShow() {
        $where[] = ['status','=',1];
        $list = Db::table('mp_slideshow')->where($where)->order(['sort'=>'ASC'])->select();
        return ajax($list);
    }
    //获取奖品列表,未到开奖时间的
    public function getPrizelist() {
        $where[] = ['status','=',1];
        $where[] = ['open_time','<',time()];
        $openid = $this->myinfo['openid'];
        $myjoin = Db::table('mp_prize_actor')->where('openid','=',$openid)->column('prize_id');
        $list = Db::table('mp_prize')->where($where)->field('id,title,cover')->order(['sort'=>'ASC'])->select();
        foreach ($list as &$v) {
            if(in_array($v['id'],$myjoin)) {
                $v['join'] = 1;
            }else {
                $v['join'] = 0;
            }
        }
        return ajax($list);
    }
    //获取奖品信息
    public function getPrizeinfo() {
        $val['prize_id'] = input('post.prize_id');
        $this->checkPost($val);

        $map[] = ['id','=',$val['prize_id']];
        $exist = Db::table('mp_prize')->where($map)->field('id,title,cover,start_time,end_time,open_time,status')->find();
        if(!$exist) {
            return ajax([],-3);
        }

        $myjoin = Db::table('mp_prize_actor')->where('openid','=',$this->myinfo['openid'])->column('prize_id');
        if(in_array($val['prize_id'],$myjoin)) {
            $exist['join'] = 1;
        }
        return ajax($exist,1);
    }
    //抽奖
    public function luckyDraw() {
        $openid = $this->myinfo['openid'];
        $val['tel'] = input('post.tel');
        $val['address'] = input('post.address');
        $val['prize_id'] = input('post.prize_id');
        $this->checkPost($val);

        if(!is_tel($val['tel'])) {
            return ajax([],14);
        }

        $myjoin = Db::table('mp_prize_actor')->where('openid','=',$openid)->column('prize_id');

        $map[] = ['id','=',$val['prize_id']];
        $exist = Db::table('mp_prize')->where($map)->find();
        if(!$exist) {
            return ajax([],-3);
        }
        if(in_array($val['prize_id'],$myjoin)) {
            return ajax([],26);
        }
        if(time() < $exist['start_time']) {
            return ajax([],27);
        }
        if(time() >= $exist['end_time'] || $exist['status'] != 1) {
            return ajax([],28);
        }

        $times = Db::table('mp_user')->where('openid','=',$openid)->value('times');
        if($times <= 0) {
            return ajax([],25);
        }

        Db::startTrans();
        try {
            $val['openid'] = $this->myinfo['openid'];
            $val['create_time'] = time();
            Db::table('mp_prize_actor')->insert($val);
            $res = Db::table('mp_user')->where('openid','=',$openid)->setDec('times');
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            return ajax($e->getMessage(),-1);
        }

        if($res) {
            return ajax([],1);
        }else {
            return ajax('操作失败',-1);
        }

    }







    private function sortMerge($node,$pid=0)
    {
        $arr = array();
        foreach($node as $key=>$v)
        {
            if($v['pid'] == $pid)
            {
                $v['child'] = $this->sortMerge($node,$v['id']);
                $arr[] = $v;
            }
        }
        return $arr;
    }

}