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


class CompFinanceModel extends Model
{
    //添加企业信息
    public function addCompFinance($data){

        //添加到公司信息表
        $data['add_time']=date('Y-m-d H:i:s');
        $result_id=$this->insertGetId($data);
        $comp_id=$data['comp_id'];
        if($result_id !=false){
            unset($data['comp_id']);unset($data['add_time']);
            $result_list=$this->addScoreRole($data);
            $i = 0;$score_num=0;
            if(is_array($result_list['b'])){
                foreach ($result_list['b'] as $key => $value) {
                    $app[$i]['comp_id']=$comp_id;
                    $app[$i]['score']=isset($result_list['a'][$key]['score'])?$result_list['a'][$key]['score']:0;
                    $app[$i]['score_source']=$result_list['a'][$key]['remark'];
                    $app[$i]['department_type']='金融部数据';
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
                    'finance_score'=>$score_num,
                ];
                Db::name('comp_score')->where('comp_id',$comp_id)->update($comp_score);
            }

            return $result_id;
        }
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

    public function editCompFinance($data){

        var_dump($data);

        $where['comp_id']=$data['comp_id'];
        $where['key_name']=array_keys($data)[1];
        $result=Db::name('comp_score_log')->where($where)->find();
        echo 'ss';
        echo 'ss';
        echo 'ss';

        if(is_array($result)){
            $app['comp_id']=$data['comp_id'];
            $app['score']='-1';
            $app['score_source']='选择否减1分';
            $app['department_type']='金融部数据';
            $app['add_time']=date('Y-m-d H:i:s');
            $app['key_name']=$where['key_name'];
            $app['ip']=get_client_ip();
        }
    }

}