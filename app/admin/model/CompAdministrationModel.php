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


class CompAdministrationModel extends Model
{
    //添加企业信息
    public function addCompAdministration($data){

        //添加到公司信息表
        $result_id=$this->insertGetId($data);
        $comp_id=$data['comp_id'];
        if($result_id !=false){
            unset($data['comp_id']);
            $result_list=$this->scoreRole($data);
            $i = 0;$score_num=0;
            foreach ($result_list['b'] as $key => $value) {
                if ($value!='') {
                    $app[$i]['comp_id']=$comp_id;
                    $app[$i]['score']=isset($result_list['a'][$key]['score'])?$result_list['a'][$key]['score']:0;
                    $app[$i]['score_source']=$result_list['a'][$key]['remark'];
                    $app[$i]['department_type']='行政部数据';
                    $app[$i]['add_time']=date('Y-m-d H:i:s');
                    $app[$i]['key_name']=$key;
                    $app[$i]['ip']=get_client_ip();
                    $score_num +=$result_list['a'][$key]['score'];
                    $i += 1;
                }
            }
            //添加到log表计算分数
            if(Db::name('comp_score_log')->insertAll($app)){
                $comp_score_msg=self::getCompScoreBasic($comp_id);
                $total_num=$comp_score_msg['total_score']+$score_num;
                $comp_score=[
                    'comp_id'=>$comp_id,
                    'total_score'=>$total_num,
                    'admin_score'=>$score_num,
                ];
                Db::name('comp_score')->where('comp_id',$comp_id)->update($comp_score);
            }

            return $result_id;
        }
    }
    //分数计算规则
    public function scoreRole($data){
        //bank_credit //是否被银行列入不诚信名单，是或者否   选择否， 记3分
        //abnormal_operation //是否被列入经营异常名录      选择否，记2分
        //illegal_dishonesty //否被列入严重违法失信企业名单 选择否，记2分
        //legal_disputes //企业民事法律纠纷次数            0记两分
        //civil_law //股东、法人、高管民事法律纠纷次数       0记两分
        //criminal_law //股东、法人、高管刑事法律纠纷次数    0记两分
        //is_website //是否有公司官网 0记两分              选择是， 记1分
        //evil_network //是否有网络搜索恶评 0记两分         选择是， 记1分

        $account_score =[];

        //是否被银行列入不诚信名单，是或者否   选择否， 记3分
        if($data['bank_credit']=='否'){
            $account_score['bank_credit']=["remark" => "选择否记3分", "score" => "3"];
        }else{
            unset($data['bank_credit']);
        }
        //是否被列入经营异常名录
        if($data['abnormal_operation']=='否'){
            $account_score['abnormal_operation']=["remark" => "选择否记2分", "score" => "2"];
        }else{
            unset($data['abnormal_operation']);
        }
        //否被列入严重违法失信企业名单 选择否，记2分
        if($data['illegal_dishonesty']=='否'){
            $account_score['illegal_dishonesty']=["remark" => "选择否记2分", "score" => "2"];
        }else{
            unset($data['illegal_dishonesty']);
        }
        //企业民事法律纠纷次数0次记2分
        if($data['legal_disputes']==0){
            $account_score['legal_disputes']=["remark" => "输入0次记2分", "score" => "2"];
        }else{
            unset($data['legal_disputes']);
        }

        //股东、法人、高管民事法律纠纷次数
        if($data['civil_law']==0){
            $account_score['civil_law']=["remark" => "输入0次记2分", "score" => "2"];
        }else{
            unset($data['civil_law']);
        }

        //股东、法人、高管刑事法律纠纷次数选择否，记2分
        if($data['criminal_law']==0){
            $account_score['criminal_law']=["remark" => "输入0次记2分", "score" => "2"];
        }else{
            unset($data['criminal_law']);
        }
        //是否有公司官网选择是， 记1分
        if($data['is_website']=='是'){
            $account_score['is_website']=["remark" => "选择是记1分", "score" => "1"];
        }else{
            unset($data['is_website']);
        }
        //是否有网络搜索恶评选择是记1分
        if($data['evil_network']=='是'){
            $account_score['evil_network']=["remark" => "选择是记1分", "score" => "1"];
        }else{
            unset($data['evil_network']);
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