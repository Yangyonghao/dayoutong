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


class CompBasicFinanceModel extends Model
{
    //添加企业信息
    public function addCompBasicFinance($data){

        //添加到公司信息表
        $data['add_time']=date('Y-m-d H:i:s');
        $result_id=$this->insertGetId($data);
        $comp_id=$data['comp_id'];
        if($result_id !=false){
            unset($data['comp_id']);unset($data['gross_profit_rate']);unset($data['add_time']);
            $result_list=$this->addScoreRole($data);
            $i = 0;$score_num=0;
            if(is_array($result_list['b'])){
                foreach ($result_list['b'] as $key => $value) {
                    $app[$i]['comp_id']=$comp_id;
                    $app[$i]['score']=isset($result_list['a'][$key]['score'])?$result_list['a'][$key]['score']:0;
                    $app[$i]['score_source']=$result_list['a'][$key]['remark'];
                    $app[$i]['department_type']='财务部数据';
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
                    'account_score'=>$score_num,
                ];
                Db::name('comp_score')->where('comp_id',$comp_id)->update($comp_score);
            }

            return $result_id;
        }
    }
    //分数计算规则
    public function addScoreRole($data){
        $account_score =[];

        //是否缴纳记账费
        if($data['agency_fee']=='是'){
            $account_score['agency_fee']=["remark" => "缴纳记账费,加5分", "score" => "+5"];
        }else{
            unset($data['agency_fee']);
        }
        //发票版本
        if($data['invoice_version']=='万元版'){
            $account_score['invoice_version']=["remark" => "发票版本选择万元版，加2分", "score" => "+2"];
        }elseif ($data['invoice_version']=='十万版'){
            $account_score['invoice_version']=["remark" => "发票版本选择十万版，加3分", "score" => "+3"];
        }elseif ($data['invoice_version']=='百万版'){
            $account_score['invoice_version']=["remark" => "发票版本选择百万版，加4分", "score" => "+4"];
        }elseif ($data['invoice_version']=='千万元版'){
            $account_score['invoice_version']=["remark" => "发票版本选择千万元版，加5分", "score" => "+5"];
        }else{
            unset($data['invoice_version']);
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
        $business_id=$param['basic_finance_id'];unset($param['basic_finance_id']);
        //添加到公司信息表
        $result_id=Db::name('comp_basic_finance')->where('id',$business_id)->update($param);
        return $result_id;
    }
}