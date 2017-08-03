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

    public function addCompBasic($data){

        $data['comp_aptitude']=rtrim(implode('|',$data['check_box']),'|');
        $data=[
            'comp_name' => "沙发斯蒂芬",
            'comp_classify'=> "贸易型",
            'reg_time' => "2017-08-07",
            'reg_money' => "240000",
            'legal_person' => "石博天",
            'link_addr' => "武汉市江汉区手动阀",
            'business_license_pic'=> "20170802/bb7572f1229c6274b364787bf98cd22d.jpg",
            'service_pay' => "是",
            'comp_aptitude' => '成品油经营资质|进出口贸易证|进出口贸易证'
        ];
        $artitude_score_count=count(explode('|',$data['comp_aptitude']));
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
        if($data['service_pay']=='是'){
            $account_score['service_pay'] = ["remark"=>"支付服务费记5分","score" => "5"];
        }else{
            unset($data['service_pay']);
        }
        $i = 0;
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $app[$i]['comp_id']=2;
                $app[$i]['score']=isset($account_score[$key]['score'])?$account_score[$key]['score']:0;
                $app[$i]['score_source']=$account_score[$key]['remark'];
                $app[$i]['add_time']=date('Y-m-d H:i:s');
                $app[$i]['department_type']='会员部数据';
                $i += 1;
            }
        }
        Db::name('comp_score_log')->insertAll($app);
        unset($data['check_box']);

        $result_id=$this->insertGetId($data);
        if($result_id !=false){
            return $result_id;
        }
    }
}