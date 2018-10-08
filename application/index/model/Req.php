<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/7
 * Time: 17:17
 */
namespace app\index\model;
use think\Model;

class Req extends Model
{
    protected $pk = 'id';
    protected $table = 'mp_req';

    protected static function init()
    {

    }

    public static function sortlist($condition = [],$lon = "117.04712",$lat = "39.064491",$page = 0,$perpage = 20) {
        $sql = "";
        if($condition['city']) {
            $city = $condition['city'];
            $sql .= " AND re.city='{$city}'";
        }

        if($condition['county']) {
            $county = $condition['county'];
            $sql .= " AND re.county='{$county}'";
        }

        if($condition['page']) {
            $page = $condition['page'] - 1;
        }
        if($condition['perpage']) {
            $perpage = $condition['perpage'];
        }

        $result = self::query("SELECT *,(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*({$lon}-r.lon)/360),2)+COS(PI()*{$lat}/180)* COS(r.lat * PI()/180)*POW(SIN(PI()*({$lat}-r.lat)/360),2)))) AS juli
FROM (SELECT re.*,u.nickname,u.avatar FROM mp_req re LEFT JOIN mp_user u ON re.f_openid=u.openid WHERE 1 " .$sql. ") r ORDER BY juli ASC LIMIT {$page},{$perpage};");
        return $result;
    }





}