<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/25
 * Time: 16:12
 */
namespace app\admin\controller;

use think\Controller;

class Common extends Controller {

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->weburl = 'mp.jianghairui.com';
        if(!$this->needSession()) {
            $this->error('请登录后操作',url('Login/index'));
        }
    }

    public function needSession() {
        $noNeedSession = [
            'Login/index',
            'Login/vcode',
            'Login/login',
            'Login/test',
        ];
        if (in_array(request()->controller() . '/' . request()->action(), $noNeedSession)) {
            return true;
        }else {
            if(session('username') && session('mploginstatus') && session('mploginstatus') == md5(session('username') . 'jiang')) {
                return true;
            }else {
                return false;
            }
        }
    }

    protected function upload($k) {
        if($this->checkfile($k) !== true) {
            return array('error'=>1,'msg'=>$this->checkfile($k));
        }

        $filename_array = explode('.',$_FILES[$k]['name']);
        $ext = array_pop($filename_array);

        $path =  'static/upload/' . date('Y-m-d');
        is_dir($path) or mkdir($path,0777,true);
        //转移临时文件
        $newname = create_unique_number() . '.' . $ext;
        move_uploaded_file($_FILES[$k]["tmp_name"], $path . "/" . $newname);
        $arr[] = $path . "/" . $newname;

        return array('error'=>0,'data'=>$arr);
    }

    protected function multi_upload() {
        foreach ($_FILES as $k=>$v) {
            if($v['name'] == '') {
                unset($_FILES[$k]);
            }else {
                if($this->checkfile($k) !== true) {
                    return array('error'=>1,'msg'=>$this->checkfile($k));
                }
            }
        }
        $arr = array();
        if(count($arr) > 3) {
            return array('error'=>1,'msg'=>'图片不可超过三张');
        }
        foreach ($_FILES as $k=>$v) {
            $filename_array = explode('.',$_FILES[$k]['name']);
            $ext = array_pop($filename_array);

            $path =  'Public/Uploads/' . date('Y-m-d');
            is_dir($path) or mkdir($path,0777,true);
            //转移临时文件
            $newname = create_unique_number() . '.' . $ext;
            move_uploaded_file($_FILES[$k]["tmp_name"], $path . "/" . $newname);
            $arr[] = $path . "/" . $newname;
        }
        return array('error'=>0,'data'=>$arr);
    }

    //检验格式大小
    private function checkfile($file) {
        $allowType = array(
            "image/gif",
            "image/jpeg",
            "image/png",
            "image/pjpeg",
            "image/bmp"
        );
        if(!in_array($_FILES[$file]["type"],$allowType)) {
            return 'invalid fileType :' . $_FILES[$file]["name"];
        }
        if($_FILES[$file]["size"] > 1024*512) {
            return 'fileSize not exceeding  512Kb :' . $_FILES[$file]["name"];
        }
        if ($_FILES[$file]["error"] > 0) {
            return "error: " . $_FILES[$file]["error"];
        }else {
            return true;
        }
    }



}