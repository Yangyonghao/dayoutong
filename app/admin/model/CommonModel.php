<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/9
 * Time: 14:54
 */
namespace app\admin\model;
use think\Db;
use think\Model;
use think\Cache;

class CommonModel extends Model
{
    function calculate_score($total_score,$param)
    {
        $opt=substr($param,0,1);
        $change_score=substr($param,-1);
        switch ($opt) {
            case "+":
                $total = $total_score + $change_score;
                break;
            case "-":
                $total = $total_score - $change_score;
                break;
        }
        return $total;
    }

    public function findCompOne($param){
        $result=Db::name('comp_basic')->where($param)->find();
        return $result;
    }
}