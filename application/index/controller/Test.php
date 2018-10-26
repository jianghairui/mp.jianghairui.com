<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
class Test extends Controller
{
    private function index()
    {
        $data1 = array(
            'username' => '姜海蕤',
            'gender' => 1,
            'address' => '天津市西青区张家窝镇灵泉北里8号楼2门501'
        );
        $data2 = array(
            'username' => '张涛',
            'gender' => 1,
            'address' => '天津市西青区张家窝镇灵泉北里8号楼2门501'
        );
        $data3 = array(
            'username' => '孙珂珺',
            'gender' => 1,
            'address' => '天津市西青区张家窝镇灵泉北里8号楼2门501'
        );
//        $redis = mredis()->set('username',$data,30);
//        $redis = mredis()->LPush('prize_actor_list',$data1);
//        halt($redis);
    }

    private function test() {
        $list = Db::table('mp_req')->where('id','not in',[18,19])->column('image');
//        $arr = [];
//        foreach ($list as $v) {
//            foreach (unserialize($v) as $vv) {
//                $arr[] = $vv;
//                @unlink($vv);
//            }
//        }
//        halt($arr);
    }




    private function log() {
        $file= ROOT_PATH . '/notify.txt';
        $text='记录时间 ---' . date('Y-m-d H:i:s') . "\n" .var_export(['a','b','c'],true). '---END---' . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }

    public function uploadImage() {
        if(!empty($_FILES)) {
//            return ajax($_FILES);
            if(count($_FILES) > 1) {
                return ajax('最多上传一张图片',9);
            }
            $info = $this->upload(array_keys($_FILES)[0]);
            if($info['error'] === 0) {
                return ajax(['path'=>$info['data']]);
            }else {
                return ajax($info['msg'],9);
            }
        }else {
            return ajax('请上传图片',30);
        }
    }

    protected function upload($k) {
        if($this->checkfile($k) !== true) {
            return array('error'=>1,'msg'=>$this->checkfile($k));
        }

        $filename_array = explode('.',$_FILES[$k]['name']);
        $ext = array_pop($filename_array);

        $path =  'static/tmp/';
        is_dir($path) or mkdir($path,0755,true);
        //转移临时文件
        $newname = create_unique_number() . '.' . $ext;
        move_uploaded_file($_FILES[$k]["tmp_name"], $path . $newname);
        $filepath = $path . $newname;

        return array('error'=>0,'data'=>$filepath);
    }

    //检验格式大小
    private function checkfile($file) {
        $allowType = array(
            "image/gif",
            "image/jpeg",
            "image/jpg",
            "image/png",
            "image/pjpeg",
            "image/bmp"
        );
        if($_FILES[$file]["type"] == '') {
            return '图片存在中文名或超过2M';
        }
        if(!in_array($_FILES[$file]["type"],$allowType)) {
            return '图片格式无效' . $_FILES[$file]["type"];
        }
        if($_FILES[$file]["size"] > 1024*1024) {
            return '图片大小不超过1MB';
        }
        if ($_FILES[$file]["error"] > 0) {
            return "error: " . $_FILES[$file]["error"];
        }else {
            return true;
        }
    }


}
