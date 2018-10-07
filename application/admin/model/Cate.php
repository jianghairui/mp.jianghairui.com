<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/7
 * Time: 17:17
 */
namespace app\admin\model;

use think\Model;

class Cate extends Model
{
    protected $pk = 'id';
    protected $table = 'mp_cate';

    protected static function init()
    {
        Cate::event('before_insert', function ($cate) {
            $cate->cate_name = 'OK SYOUNARA';
        });
    }



}