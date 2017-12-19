<?php
use think\Db;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function get_distance($lat1,$lng1,$lat2,$lng2){
     $lat = $lat1-$lat2;
     $lng = $lng1-$lng2;
     $distance= 2 * 6378.137* asin(sqrt(pow(sin(pi() * ($lat) / 360), 2) + cos(pi() * $lat1 / 180)* cos($lat2* pi() / 180) * pow(sin(pi() * ($lng) / 360), 2)));
     $distance*=1000;
     RETURN round($distance);
}

function chk($db_name,$field_name,$param,$case=''){
    if (!$db_name || !$field_name){
        die('验证参数名称错误！');
    }

    $res=Db::query("SELECT id AS count FROM $db_name WHERE $field_name=$param limit 1");
    if (count($res)){
        return true;
    }else{
        return false;
    }
}

/**
 * @param $num 长度
 * @return null|string 随机组合字符串
 */
function get_salt($num){
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol)-1;
    for($i=0;$i<$num;$i++){
        $str.=$strPol[rand(0,$max)];
    }
    return $str;
}