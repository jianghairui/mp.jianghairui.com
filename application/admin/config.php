<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/25
 * Time: 13:57
 */
return array(
    'template'  =>  [
        'layout_on'     =>  true,
        'layout_name'   =>  'public/layout',
    ],
    'session'   => [
        'prefix'        => 'jiang',
        'type'          => '',
        'auto_start'    => true,
    ],
    'login_key' => 'jiang',
    'app_trace' => true,
    'trace'     =>  [
        //支持Html,Console
        'type'  =>  'html',
    ]
);