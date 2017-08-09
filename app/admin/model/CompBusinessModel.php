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


class CompBusinessModel extends Model
{
    //添加企业信息
    public function addCompBusiness($data){

        //添加到公司信息表
        $result_id=$this->insertGetId($data);
        $comp_id=$data['comp_id'];
        if($result_id !=false){
            unset($data['comp_id']);
            $result_list=$this->scoreRole($data);
            $i = 0;$score_num=0;
            foreach ($result_list['b'] as $key => $value) {
                if (!empty($value)) {
                    $app[$i]['comp_id']=$comp_id;
                    $app[$i]['score']=isset($result_list['a'][$key]['score'])?$result_list['a'][$key]['score']:0;
                    $app[$i]['score_source']=$result_list['a'][$key]['remark'];
                    $app[$i]['department_type']='业务部数据';
                    $app[$i]['add_time']=date('Y-m-d H:i:s');
                    $app[$i]['key_name']=$key;
                    $app[$i]['ip']=get_client_ip();
                    $score_num +=$result_list['a'][$key]['score'];
                    $i += 1;
                }
            }
            if(Db::name('comp_score_log')->insertAll($app)){
                $comp_score_msg=self::getCompScoreBasic($comp_id);
                $total_num=$comp_score_msg['total_score']+$score_num;
                $comp_score=[
                    'comp_id'=>$comp_id,
                    'total_score'=>$total_num,
                    'sales_score'=>$score_num,
                ];
                Db::name('comp_score')->where('comp_id',$comp_id)->update($comp_score);
            }

            return $result_id;
        }
    }
    //分数计算规则
    public function scoreRole($data){
        $account_score =[];

        //是否有仓库
        if($data['storage']=='是'){
            $account_score['storage']=["remark" => "选择是记2分", "score" => "+2"];
        }else{
            unset($data['storage']);
        }
        //是否有物流
        if($data['logistics']=='是'){
            $account_score['logistics']=["remark" => "选择是记2分", "score" => "+2"];
        }else{
            unset($data['logistics']);
        }
        //是否有货物质量问题
        if($data['oil_quality']=='是'){
            $account_score['oil_quality']=["remark" => "选择是记2分", "score" => "+2"];
        }else{
            unset($data['oil_quality']);
        }
        //是否有货物质量问题 是否有货物质量问题，是或者否 选择是，记2分
        if($data['collection']=='较好'){
            $account_score['collection']=["remark" => "选择较好记5分", "score" => "+5"];
        }elseif($data['collection']=='正常') {
            $account_score['collection']=["remark" => "选择正常记2分", "score" => "+3"];
        }elseif ($data['collection']=='一般'){
            $account_score['collection']=["remark" => "选择一般记2分", "score" => "+1"];
        }else{
            unset($data['collection']);
        }
        //请选择交易频次 交易频次 频繁记5分、较频繁记4分、一般记3分、较少记2分、极少记1分
        if($data['transaction_num']=='频繁'){
            $account_score['transaction_num']=["remark" => "选择频繁记5分", "score" => "+5"];
        }elseif($data['transaction_num']=='较频繁') {
            $account_score['transaction_num']=["remark" => "选择较频繁记4分", "score" => "+4"];
        }elseif ($data['transaction_num']=='一般'){
            $account_score['transaction_num']=["remark" => "选择一般记3分", "score" => "+3"];
        }elseif($data['transaction_num']=='较少') {
            $account_score['transaction_num']=["remark" => "选择较少记2分", "score" => "+2"];
        }elseif ($data['transaction_num']=='极少'){
            $account_score['transaction_num']=["remark" => "选择极少记1分", "score" => "+1"];
        }else{
            unset($data['transaction_num']);
        }

        //请选择请选择 履约情况很好记4分，较好记3分，正常记2分，一般记1分
        if($data['performance']=='很好') {
            $account_score['performance']=["remark" => "选择很好记4分", "score" => "+4"];
        }elseif ($data['performance']=='较好'){
            $account_score['performance']=["remark" => "选择较好记3分", "score" => "+3"];
        }elseif($data['performance']=='正常') {
            $account_score['performance']=["remark" => "选择正常记2分", "score" => "+2"];
        }elseif ($data['performance']=='一般'){
            $account_score['performance']=["remark" => "选择极少记1分", "score" => "+1"];
        }else{
            unset($data['performance']);
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

}