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
use think\Cache;


class CompBasicModel extends Model
{
    //添加企业信息
    public function addCompBasic($data){
        //添加到公司信息表
        $data['comp_aptitude']=rtrim(implode('|',$data['check_box']),'|');
        $data['add_time']=date('Y-m-d H:i:s');
        unset($data['check_box']);
        $result_id=$this->insertGetId($data);
        if($result_id !=false){
            unset($data['status']);unset($data['add_time']);
            $artitude_score_count=count(explode('|',$data['comp_aptitude']));
            $result_list=$this->scoreRole($artitude_score_count);
            if($data['service_pay']=='是'){
                $result_list['service_pay'] = ["remark"=>"支付服务费记5分","score" => "5"];
            }else{
                unset($data['service_pay']);
            }
            $i = 0;$score_num=0;
            foreach ($data as $key => $value) {
                if (!empty($value)) {
                    $app[$i]['comp_id']=$result_id;
                    $app[$i]['score']=isset($result_list[$key]['score'])?$result_list[$key]['score']:0;
                    $app[$i]['score_source']=$result_list[$key]['remark'];
                    $app[$i]['department_type']='会员部数据';
                    $app[$i]['add_time']=date('Y-m-d H:i:s');
                    $app[$i]['key_name']=$key;
                    $app[$i]['ip']=get_client_ip();
                    $score_num +=$result_list[$key]['score'];
                    $i += 1;
                }
            }

            Db::name('comp_score_log')->insertAll($app);
            $comp_score=[
                'comp_id'=>$result_id,
                'total_score'=>$score_num,
                'member_score'=>$score_num,
                'finance_score'=>0,
                'sales_score'=>0,
                'account_score'=>0,
                'admin_score'=>0,
            ];
            Db::name('comp_score')->insert($comp_score);

            return $result_id;
        }
    }
    //分数计算规则
    public function scoreRole($artitude_score_count){
        $account_score = array(
            "comp_name"            => array("remark" => "添加企业名称，加1分", "score" => "+1"),
            'comp_classify'        => array("remark" => "添加企业分类，分1分","score" => "+1"),
            'reg_time'             => array("remark" => "填写成立时间，加1分","score" => "+1"),
            'reg_money'            => array("remark" => "填写注册资本，加1分","score" => "+1"),
            'legal_person'         => array("remark" => "填写企业法人，加1分","score" => "+1"),
            'link_addr'            => array("remark" => "填写企业联系地址，加1分","score" => "+1"),
            'business_license_pic' => array("remark" => "上传营业执照，加1分","score" => "+1"),
            'comp_aptitude'        => array("remark" => '添加附加资质，加'.$artitude_score_count.'分',"score" => '+'.$artitude_score_count),
        );
        return $account_score;
    }
    /*
     * @function：编辑企业基本信息
     * @date:20170814
     * */
    public function editCompBasic($param){
        $basic_id=$param['basic_id'];
        unset($param['basic_id']);unset($param['check_box']);
        $result_id=Db::name('comp_basic')->where('id',$basic_id)->update($param);
        return $result_id;
    }

    /*
     * 批量循环导入并计算分数
     * */
    public function excelAddCompBasic($data){
        //添加到公司信息表
        foreach ($data as $k=>$v){
            $v['add_time']=date('Y-m-d H:i:s');
            $result=self::findCompOne(['comp_name'=>$v['comp_name']]);
            if(!empty($result)){
                unset($data[$k]);
                continue;
            }
            $result_id=Db::name('comp_basic')->insertGetId($v);
            $data[$k]['comp_id']=$result_id;
        }
        if(empty($data)){
            return false;
        }

        foreach ($data as $i=>$j){
            unset($j['add_time']);
            $artitude_score_count=count(explode('|',$j['comp_aptitude']));
            $result_list[$i]=$this->scoreRole($artitude_score_count);
            if(!isset($j['business_license_pic'])){
                unset($result_list[$i]['business_license_pic']);
            }
            if($j['service_pay']=='是'){
                $result_list[$i]['service_pay'] = ["remark"=>"支付服务费，加5分","score" => "+5"];
            }else{
                unset($j['service_pay']);
            }
        }
        $i = 0;
        foreach ($data as $key => $value) {
            $score_num[$key]=[];$score_num[$key]['score']=0;
            if (!empty($value)) {
                foreach ($result_list[$key] as $m=>$n){
                    $app[$i]['comp_id']=$value['comp_id'];
                    $app[$i]['score']=isset($n['score'])?$n['score']:0;
                    $app[$i]['score_source']=isset($n['remark'])?$n['remark']:0;
                    $app[$i]['department_type']='会员部数据';
                    $app[$i]['add_time']=date('Y-m-d H:i:s');
                    $app[$i]['key_name']=$m;
                    $app[$i]['ip']=get_client_ip();
                    $score_num[$key]['score'] +=substr($n['score'],1);
                    $score_num[$key]['comp_id'] =$value['comp_id'];
                    Db::name('comp_score_log')->insert($app[$i]);
                    $i += 1;
                }
            }
        }
        foreach ($score_num as $v=>$x){
            $comp_score[$v]=[
                'comp_id'=>$x['comp_id'],
                'total_score'=>$x['score'],
                'member_score'=>$x['score'],
                'finance_score'=>0,
                'sales_score'=>0,
                'account_score'=>0,
                'admin_score'=>0,
            ];
            Db::name('comp_score')->insert($comp_score[$v]);
        }


        return true;
    }

    public function findCompOne($param){
        $result=Db::name('comp_basic')->where($param)->find();
        return $result;
    }
}