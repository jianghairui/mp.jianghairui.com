<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;

class Test extends Controller
{
    public function _initialize()
    {
        parent::_initialize();
//        echo '初始化函数<br>';
    }

    public function addCity()
    {
        $code = '110100000000';
        $code = substr($code, 0,4);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/'. substr($code, 0,2) . '/' . $code . '.html');curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);curl_close($curl);$data = mb_convert_encoding($data, 'UTF-8', 'GBK');
// 裁头
        $offset = @mb_strpos($data, 'countytr',2000,'GBK');
        if (!$offset) {
            die('DIE');
        }
        $data = mb_substr($data, $offset,NULL,'GBK');
// 裁尾
        $offset = mb_strpos($data, '</TABLE>', 200,'GBK');
        $data = mb_substr($data, 0, $offset,'GBK');
        preg_match_all('/\d{12}|[\x7f-\xff]+/', $data, $out);
        $out = $out[0];
// 某个城市
        $list = [];
        for ($j=0; $j < count($out) ; $j++) {
            $list[] = [
                'code'=> $out[$j],
                'name'=> $out[++$j],
                'pid' => '1',
            ];
        }
//        $res = Db::table('mp_city')->insertAll($list);
//        halt($res);
    }

    public function getCity()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/' . 11 . '.html');curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
        // 裁头
        $offset = mb_strpos($data, 'citytr',2000,'GBK');
        $data = mb_substr($data, $offset,NULL,'GBK');
        // 裁尾
        $offset = mb_strpos($data, '</TABLE>', 200,'GBK');
        $data = mb_substr($data, 0, $offset,'GBK');
        preg_match_all('/\d{12}|[\x7f-\xff]+/', $data, $city);
        $city = $city[0];
        var_dump($city);
        $list = [];
        for ($j=0; $j < count($city) ; $j++) {
            $list[] = [
                'code'=> $city[$j],
                'name'=> $city[++$j],
            ];
        }
        halt($list);
    }

    public function getProvince()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/index.html');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
// 裁头
        $offset = mb_strpos($data, 'provincetr',2000,'GBK');
        $data = mb_substr($data, $offset,NULL,'GBK');
// 裁尾
        $offset = mb_strpos($data, '</TABLE>', 200,'GBK');
        $data = mb_substr($data, 0, $offset,'GBK');
        preg_match_all('/\d{2}|[\x7f-\xff]+/', $data, $out);
        $province = $out[0];
        halt($province);
    }

}
