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


class CompBasicFinanceModel extends CommonModel
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
    /*
     * @author:yangyh
     * @date:20171219
     * @function:导入基本财务信息
     * */
    public function excelAddBasicFinance($data){
        //添加到公司金融数据表
        $param=[];
        foreach ($data as $k => $v) {
            //根据名称查询公司是否存在
            $result = parent::findCompOne(['comp_name' => $v['comp_name']]);
            if (!empty($result)) {
                $comp_basic_finance=Db::name('comp_basic_finance')->where(['comp_id'=>$result['id']])->find();
                if(!empty($comp_basic_finance)){
                    continue;
                }else{
                    $v['add_time'] = date('Y-m-d H:i:s');
                    $v['comp_id']  = $result['id'];
                    unset($v['comp_name']);
                    $param[]=$v;
                }
            }
        }

        //添加到分数log日志
        $finance_log=[];
        $av=0;$score=[];
        foreach($param as $i=>$j){
            $sss=self::addScoreRole($j);
            if(!empty($sss['a'])){
                $score[$i]['score']=0;
                foreach ($sss['a'] as $a=>$v){
                    $finance_log[$av]['comp_id']=$j['comp_id'];
                    $finance_log[$av]['score']=$v['score'];
                    $finance_log[$av]['score_source']=$v['remark'];
                    $finance_log[$av]['department_type']='财务部数据';
                    $finance_log[$av]['add_time']=date("Y-m-d H:i:s");
                    $finance_log[$av]['key_name']=$a;
                    $finance_log[$av]['ip']=get_client_ip();
                    $score[$i]['score'] +=substr($v['score'],1);
                    $score[$i]['comp_id']=$j['comp_id'];
                    $av++;
                }
            }
        }
        //开启事务
        Db::startTrans();
        try{
            Db::name("comp_basic_finance")->insertAll($param);
            Db::name("comp_score_log")->insertAll($finance_log);
            $basic_score=[];
            foreach ($score as $m=>$n){
                $fields="comp_id,total_score,id";
                $score_arr=Db::name('comp_score')->field($fields)->where(['comp_id'=>$n['comp_id']])->find();
                $basic_score['total_score']   =$score_arr['total_score']+$n['score'];
                $basic_score['account_score'] =$n['score'];
                //更新分数表
                Db::name('comp_score')->where(['id'=>$score_arr['id']])->update($basic_score);
            }
            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }
}