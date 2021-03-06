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


class CompAdministrationModel extends CommonModel
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
            $account_score['bank_credit']=["remark" => "没有被银行列入不诚信名单，加3分", "score" => "+3"];
        }else{
            unset($data['bank_credit']);
        }
        //是否被列入经营异常名录
        if($data['abnormal_operation']=='否'){
            $account_score['abnormal_operation']=["remark" => "没有被列入经营异常名录，加2分", "score" => "+2"];
        }else{
            unset($data['abnormal_operation']);
        }
        //否被列入严重违法失信企业名单 选择否，记2分
        if($data['illegal_dishonesty']=='否'){
            $account_score['illegal_dishonesty']=["remark" => "没有被列入严重违法失信企业名单，加2分", "score" => "+2"];
        }else{
            unset($data['illegal_dishonesty']);
        }
        //企业民事法律纠纷次数0次记2分
        if($data['legal_disputes']==0){
            $account_score['legal_disputes']=["remark" => "企业民事法律纠纷".$data['legal_disputes']."次,加2分", "score" => "+2"];
        }else{
            unset($data['legal_disputes']);
        }

        //股东、法人、高管民事法律纠纷次数
        if($data['civil_law']==0){
            $account_score['civil_law']=["remark" => "股东、法人、高管民事法律纠纷".$data['civil_law']."次，加2分", "score" => "+2"];
        }else{
            unset($data['civil_law']);
        }

        //股东、法人、高管刑事法律纠纷次数选择否，记2分
        if($data['criminal_law']==0){
            $account_score['criminal_law']=["remark" => "股东、法人、高管刑事法律纠纷".$data['criminal_law']."次，加2分", "score" => "+2"];
        }else{
            unset($data['criminal_law']);
        }
        //是否有公司官网选择是， 记1分
        if($data['is_website']=='是'){
            $account_score['is_website']=["remark" => "有公司官网记1分", "score" => "+1"];
        }else{
            unset($data['is_website']);
        }
        //是否有网络搜索恶评选择是记1分
        if($data['evil_network']=='否'){
            $account_score['evil_network']=["remark" => "没有网络搜索恶评加1分", "score" => "+1"];
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
    /*
     * @function:修改行政部管理明细
     * @date:20170811
     * */
    public function editCompAdministration($post_param){
        $admin_id=$post_param['admin_id'];unset($post_param['admin_id']);
        //添加到公司信息表
        $result_id=Db::name('comp_administration')->where('id',$admin_id)->update($post_param);
        return $result_id;
    }


    /*
     * @function：excel导入
     * @date:201171215
     * */
    public function excelAddCompAdministration($data)
    {
        //添加到公司金融数据表
        $param=[];
        foreach ($data as $k => $v) {
            //根据名称查询公司是否存在
            $result = parent::findCompOne(['comp_name' => $v['comp_name']]);
            if (!empty($result)) {
                $comp_finance_arr=Db::name('comp_administration')->where(['comp_id'=>$result['id']])->find();
                if(!empty($comp_finance_arr)){
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
            $sss=self::scoreRole($j);
            if(!empty($sss['a'])){
                $score[$i]['score']=0;
                foreach ($sss['a'] as $a=>$v){
                    $finance_log[$av]['comp_id']=$j['comp_id'];
                    $finance_log[$av]['score']=$v['score'];
                    $finance_log[$av]['score_source']=$v['remark'];
                    $finance_log[$av]['department_type']='行政部数据';
                    $finance_log[$av]['add_time']=date("Y-m-d H:i:s");
                    $finance_log[$av]['key_name']=$a;
                    $finance_log[$av]['ip']=get_client_ip();
                    $score[$i]['score'] +=substr($v['score'],1);
                    $score[$i]['comp_id']=$j['comp_id'];
                    $av++;
                }
            }
        }
        Db::startTrans();
        try{
            Db::name('comp_administration')->insertAll($param);
            Db::name("comp_score_log")->insertAll($finance_log);
            $basic_score=[];
            foreach ($score as $m=>$n){
                $fields="comp_id,total_score,id";
                $score_arr=Db::name('comp_score')->field($fields)->where(['comp_id'=>$n['comp_id']])->find();
                $basic_score['total_score'] =$score_arr['total_score']+$n['score'];
                $basic_score['admin_score'] =$n['score'];
                //更新分数表
                Db::name('comp_score')->where(['id'=>$score_arr['id']])->update($basic_score);
            }
            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 101;
        }
    }
}