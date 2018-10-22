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
}
