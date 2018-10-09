<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/8
 * Time: 18:21
 */
namespace app\admin\controller;

use think\Exception;
use think\Db;
use think\facade\Request;
class Banner extends Common {
    //轮播图列表
    public function slideshow() {
        $list = Db::table('mp_slideshow')->order(['sort'=>'ASC'])->select();
        $this->assign('list',$list);
        return $this->fetch();
    }
    //添加轮播图POST
    public function slideadd() {
        if(Request::isAjax()) {
            $val['title'] = input('post.title');
            $this->checkPost($val);
            $val['url'] = input('post.url');

            if(isset($_FILES['file-2'])) {
                $info = $this->upload('file-2');
                if($info['error'] === 0) {
                    $val['pic'] = $info['data'];
                }else {
                    return ajax($info['msg'],-1);
                }
            }else {
                return ajax('请上传图片',-1);
            }

            try {
                $res = Db::table('mp_slideshow')->insert($val);
            }catch (Exception $e) {
                if(isset($val['pic'])) {
                    @unlink($val['pic']);
                }
                return ajax($e->getMessage(),-1);
            }
            if($res) {
                return ajax([],1);
            }else {
                if(isset($val['pic'])) {
                    @unlink($val['pic']);
                }
                return ajax('添加失败',-1);
            }
        }
    }
    //修改轮播图
    public function slidemod() {
        $val['id'] = input('param.id');
        $exist = Db::table('mp_slideshow')->where('id','=',$val['id'])->find();
        if(!$exist) {
            $this->error('非法操作',url('Banner/slideshow'));
        }
        $this->assign('info',$exist);
        return $this->fetch();
    }
    //修改轮播图POST
    public function slidemod_post() {
        if(Request::isAjax()) {
            $val['title'] = input('post.title');
            $val['id'] = input('post.slideid');
            $this->checkPost($val);
            $val['url'] = input('post.url');

            $exist = Db::table('mp_slideshow')->where('id',$val['id'])->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }

            if(isset($_FILES['file-2'])) {
                $info = $this->upload('file-2');
                if($info['error'] === 0) {
                    $val['pic'] = $info['data'];
                }else {
                    return ajax($info['msg'],-1);
                }
            }
            try {
                Db::table('mp_slideshow')->update($val);
            }catch (Exception $e) {
                if(isset($_FILES['file-2'])) {
                    @unlink($val['pic']);
                }
                return ajax($e->getMessage(),-1);
            }
            if(isset($_FILES['file-2'])) {
                @unlink($exist['pic']);
            }
            return ajax([],1);
        }
    }
    //删除轮播图
    public function slide_del() {
        $val['id'] = input('post.slideid');
        $this->checkPost($val);
        $exist = Db::table('mp_slideshow')->where('id',$val['id'])->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }
        $model = model('Slideshow');
        try {
            $model::destroy($val['id']);
        }catch (Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        return ajax([],1);
    }
    //轮播图排序
    public function sortSlide() {
        $val['id'] = input('post.id');
        $val['sort'] = input('post.sort');
        $this->checkPost($val);
        try {
            Db::table('mp_slideshow')->update($val);
        }catch (Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($val);
    }
    //禁用轮播图
    public function slide_stop() {
        $val['id'] = input('post.slideid');
        $this->checkPost($val);
        $exist = Db::table('mp_slideshow')->where('id',$val['id'])->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }

        try {
            Db::table('mp_slideshow')->where('id',$val['id'])->update(['status'=>0]);
        }catch (Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //启用轮播图
    public function slide_start() {
        $val['id'] = input('post.slideid');
        $this->checkPost($val);
        $exist = Db::table('mp_slideshow')->where('id',$val['id'])->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }

        try {
            Db::table('mp_slideshow')->where('id',$val['id'])->update(['status'=>1]);
        }catch (Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function prizelist() {
        $param['status'] = input('param.status');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',config('app.page'));
        $perpage = input('param.perpage',config('app.perpage'));

        $where = [];

        if($param['status']) {
            $where[] = ['status','=',$param['status']];
        }

        if($param['logmin']) {
            $where[] = ['start_time','>=',strtotime(date('Y-m-d',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['start_time','<=',strtotime(date('Y-m-d',strtotime($param['logmax'])))];
        }

        if($param['search']) {
            $where[] = ['title','like',"%{$param['search']}%"];
        }

        $count = Db::table('mp_prize')->where($where)->count();
        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        $list = Db::table('mp_prize')->where($where)->order(['sort'=>'ASC'])->limit(($curr_page - 1)*$perpage,$perpage)->select();
        $this->assign('list',$list);
        $this->assign('page',$page);
        return $this->fetch();
    }

    public function prizeadd() {
        return $this->fetch();
    }

    public function prizeadd_post() {
        if(Request::isAjax()) {
            $val['title'] = input('post.title');
            $val['num'] = input('post.num');
            $val['sort'] = input('post.sort');
            $val['probability'] = input('post.probability');
            $val['status'] = input('post.status');
            $val['start_time'] = input('post.start_time');
            $val['end_time'] = input('post.end_time');
            $val['open_time'] = input('post.open_time');
            $this->checkPost($val);

            $val['probability'] = round($val['probability']*100)/100;
            $val['start_time'] = strtotime($val['start_time']);
            $val['end_time'] = strtotime($val['end_time']);
            $val['open_time'] = strtotime($val['open_time']);

            if(isset($_FILES['file'])) {
                $info = $this->upload('file');
                if($info['error'] === 0) {
                    $val['cover'] = $info['data'];
                }else {
                    return ajax($info['msg'],-1);
                }
            }else {
                return ajax('请上传图片',-1);
            }

            try {
                $res = Db::table('mp_prize')->insert($val);
            }catch (Exception $e) {
                if(isset($val['cover'])) {
                    @unlink($val['cover']);
                }
                return ajax($e->getMessage(),-1);
            }
            if($res) {
                return ajax([],1);
            }else {
                if(isset($val['cover'])) {
                    @unlink($val['cover']);
                }
                return ajax('添加失败',-1);
            }
        }
    }

    public function prizemod() {
        $val['id'] = input('param.id');
        $exist = Db::table('mp_prize')->where('id','=',$val['id'])->find();
        if(!$exist) {
            $this->error('非法操作',url('Banner/prizelist'));
        }
        $this->assign('info',$exist);
        return $this->fetch();
    }

    public function prizemod_post() {
        if(Request::isAjax()) {
            $val['title'] = input('post.title');
            $val['id'] = input('post.prize_id');
            $val['num'] = input('post.num');
            $val['sort'] = input('post.sort');
            $val['probability'] = input('post.probability');
            $val['status'] = input('post.status');
            $val['start_time'] = input('post.start_time');
            $val['end_time'] = input('post.end_time');
            $val['open_time'] = input('post.open_time');

            $this->checkPost($val);

            $val['probability'] = round($val['probability']*100)/100;
            $val['start_time'] = strtotime($val['start_time']);
            $val['end_time'] = strtotime($val['end_time']);
            $val['open_time'] = strtotime($val['open_time']);

            $exist = Db::table('mp_prize')->where('id','=',$val['id'])->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            if($exist['status'] == 3) {
                return ajax('活动已结束,不可修改',-1);
            }

            if(isset($_FILES['file'])) {
                $info = $this->upload('file');
                if($info['error'] === 0) {
                    $val['cover'] = $info['data'];
                }else {
                    return ajax($info['msg'],-1);
                }
            }

            try {
                $res = Db::table('mp_prize')->update($val);
            }catch (Exception $e) {
                if(isset($val['cover'])) {
                    @unlink($val['cover']);
                }
                return ajax($e->getMessage(),-1);
            }

            if(isset($val['cover'])) {
                @unlink($exist['cover']);
            }
            return ajax([],1);
        }
    }

    public function prize_stop()
    {
        $val['id'] = input('post.id');
        $this->checkPost($val);
        $exist = Db::table('mp_prize')->where('id',$val['id'])->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }
        if($exist['status'] == 3) {
            return ajax('活动已结束,无法操作',-1);
        }

        try {
            Db::table('mp_prize')->where('id',$val['id'])->update(['status'=>2]);
        }catch (Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function prize_start()
    {
        $val['id'] = input('post.id');
        $this->checkPost($val);
        $exist = Db::table('mp_prize')->where('id',$val['id'])->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }
        if($exist['status'] == 3) {
            return ajax('活动已结束,无法操作',-1);
        }

        try {
            Db::table('mp_prize')->where('id',$val['id'])->update(['status'=>1]);
        }catch (Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function prize_del() {
        $val['id'] = input('post.id');
        $this->checkPost($val);
        $exist = Db::table('mp_prize')->where('id',$val['id'])->find();
        if(!$exist) {
            return ajax('非法操作',-1);
        }
        $model = model('Prize');
        try {
            $model::destroy($val['id']);
        }catch (Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        return ajax([],1);
    }

    public function sortPrize()
    {
        $val['id'] = input('post.id');
        $val['sort'] = input('post.sort');
        $this->checkPost($val);
        try {
            Db::table('mp_prize')->update($val);
        }catch (Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($val);
    }






}