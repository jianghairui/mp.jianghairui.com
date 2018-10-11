<?php
namespace app\index\controller;

class Test
{
    public function index()
    {
        $file= ROOT_PATH . '/notify.txt';
        $text='记录时间 ---' . date('Y-m-d H:i:s') . "\n" . '---END---' . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }
}
