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
        unset($data['check_box']);
        $result_id=$this->insertGetId($data);
        if($result_id !=false){
            unset($data['status']);
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
                    $app[$i]['add_time']=date('Y-m-d H:i:s');
                    $app[$i]['department_type']='会员部数据';
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
            "comp_name"            => array("remark" => "添加名称获取1积分", "score" => "1"),
            'comp_classify'        => array("remark" => "企业分类获取1分","score" => "1"),
            'reg_time'             => array("remark" => "填写成立时间记1分","score" => "1"),
            'reg_money'            => array("remark" => "填写注册资本记1分","score" => "1"),
            'legal_person'         => array("remark" => "填写企业法人记1分","score" => "1"),
            'link_addr'            => array("remark" => "填写地址记1分","score" => "1"),
            'business_license_pic' => array("remark" => "上传营业执照记1分","score" => "1"),
            'comp_aptitude'        => array("remark" => '添加附加资质记'.$artitude_score_count.'分',"score" => $artitude_score_count),
        );
        return $account_score;
    }
}