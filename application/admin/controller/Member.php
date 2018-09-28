<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/25
 * Time: 16:09
 */
namespace app\admin\controller;
use app\index\controller\Common;

class Member extends Common {

    public function memberlist() {
        return $this->fetch();
    }

}