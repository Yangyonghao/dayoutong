<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/3
 * Time: 10:48
 */

namespace app\admin\model;

use think\Db;
use think\Model;


class CompStatementsModel extends Model
{
    //添加基本财务信息
    public function addCompStatements($data){

        //添加到公司信息表
        $data['add_time']=date('Y-m-d H:i:s');
        $data['input_monthly']=date("Ym");
        $data['input_year']=date("Y");
        $result_id=$this->insertGetId($data);
        return $result_id;
    }
    //分数计算规则
    public function addScoreRole($data){
        $account_score =[];

        //是否有仓库
        if($data['financing']=='是'){
            $account_score['financing']=["remark" => "选择是记1分", "score" => "1"];
        }else{
            unset($data['financing']);
        }

        return ['a'=>$account_score,'b'=>$data];
    }
    /*
     * @function:获取公司的分数数据
     * @date:2017-08-05
     * @author:yangyh
     * */
    public function getCompScoreBasic($id){
        return Db::name('comp_score')->where('comp_id',$id)->find();
    }
    /*
     * @function：修改财务基本信息
     * @author：yyh
     * */
    public function editCompBasicFinance($param){
        $business_id=$param['business_id'];unset($param['business_id']);
        //添加到公司信息表
        $result_id=Db::name('comp_basic_finance')->where('id',$business_id)->update($param);
        return $result_id;
    }

}