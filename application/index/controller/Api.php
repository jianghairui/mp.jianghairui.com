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
        $condition['page'] = input('post.page',1);
        $condition['perpage'] = input('post.perpage',20);
        $condition['city'] = input('post.city');
        $condition['county'] = input('post.county');

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
    //发布需求
    public function requireRelease ()
    {
        $val['order_sn'] = create_unique_number('R');
        $val['content'] = input('post.content');
        $val['price'] = input('post.price');
        $val['rate'] = Db::table('mp_setting')->value('rate');
        $val['fee'] = $val['price'] * $val['rate'];
        $val['address'] = input('post.address');
        $val['lon'] = input('post.lon');
        $val['lat'] = input('post.lat');
        $val['num'] = input('post.num');
        $val['cate_id'] = input('post.cate_id');
        $val['create_time'] = time();
        $val['end_time'] = input('post.end_time');
        $val['deadline'] = input('post.deadline');

        $this->checkPost($val);
        $val['f_openid'] = $this->myinfo['openid'];

        if(!$this->checkExist('mp_cate',[
            ['id','=',$val['cate_id']],
            ['pid','<>',0]
            ])) {
            return ajax([],-3);
        }

        if(!is_currency($val['price'])) {
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

        $val['end_time'] = strtotime($val['end_time']);
        $val['deadline'] = strtotime($val['deadline']);

        $cityinfo = $this->getCityinfo($val['lon'],$val['lat']);

        $val['province'] = $cityinfo['province'];
        $val['city'] = $cityinfo['city'];
        $val['county'] = $cityinfo['district'];

        $info = $this->multi_upload();
        if($info['error'] === 0) {
            $val['image'] = serialize($info['data']);
        }else {
            return ajax($info['msg'],9);
        }
        try {
            Db::table('mp_require')->insert($val);
        }catch (Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);

    }

    //申请需求
    public function apply()
    {
        $val['rid'] = input('post.rid');
        $val['to_openid'] = $this->myinfo['openid'];
        $this->checkPost($val);

        $map[] = ['id','=',$val['rid']];
        $map[] = ['pay_status','=',1];
        $map[] = ['status','=',1];
        if(!$this->checkExist('mp_require',$map)) {
            return ajax([],10);
        }
        $where[] = ['rid','=',$val['rid']];
        $where[] = ['to_openid','=',$val['to_openid']];
        if($this->checkExist('mp_apply',$where)) {
            return ajax([],11);
        }

        $val['to_nickname'] = input('post.to_nickname');
        $val['to_avatar'] = input('post.to_avatar');
        $val['apply_time'] = time();


        try {
            $res = Db::table('mp_apply')->insert($val);
        }catch (Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($res) {
            return ajax([],1);
        }else {
            return ajax([],-1);
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