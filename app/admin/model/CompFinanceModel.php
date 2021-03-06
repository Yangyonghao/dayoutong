<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/3
 * Time: 10:48
 */

namespace app\admin\model;

use think\Db;
use think\Exception;
use think\Model;


class CompFinanceModel extends CommonModel
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
            $app=[];
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


            if(!empty($app) && Db::name('comp_score_log')->insertAll($app)){
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
            $account_score['financing']=["remark" => "银行融资/贷款情况选择是，加1分", "score" => "+1"];
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

    public function editCompFinance($data,$id){
        $return_msg=Db::name('comp_finance')->where('id',$id)->update($data);
        $comp_id=$data['comp_id'];
        if($return_msg){
//            Db::startTrans();
//            try{
                $where['comp_id']=$comp_id;
                $where['key_name']=array_keys($data)[1];
                $result=Db::name('comp_score_log')->where($where)->sum('score');
                if($result>0){
                    $app['score']='-1';
                    $app['score_source']='银行融资/贷款情况选择否，减1分';
                }else{
                    $app['score']='+1';
                    $app['score_source']='银行融资/贷款情况选择是，加1分';
                }
                $app['comp_id']=$comp_id;
                $app['department_type']='金融部数据';
                $app['add_time']=date('Y-m-d H:i:s');
                $app['key_name']=$where['key_name'];
                $app['ip']=get_client_ip();
                $score_status=Db::name('comp_score_log')->insertGetId($app);

//                Db::commit();
//            }catch(Exception $e){
//                // 回滚事务
//                Db::rollback();
//                $this->error($e->getMessage());
//            }

            if($score_status){
                $score=Db::name('comp_score_log')->where($where)->sum('score');
                $comp_score_msg=self::getCompScoreBasic($comp_id);
                $total_num=$this->calculate_score($comp_score_msg['total_score'],$app['score']);
                $comp_score=[
                    'comp_id'=>$comp_id,
                    'total_score'=>$total_num,
                    'finance_score'=>$score,
                ];
                Db::name('comp_score')->where('comp_id',$comp_id)->update($comp_score);
            }
        }
        return $return_msg;
    }
    /*
     * @function：excel导入
     * @date:201171215
     * */
    public function excelAddCompFinance($data)
    {
        //添加到公司金融数据表
        $param=[];
        foreach ($data as $k => $v) {
            //根据名称查询公司是否存在
            $result = parent::findCompOne(['comp_name' => $v['comp_name']]);
            if (!empty($result)) {
                $comp_finance_arr=Db::name('comp_finance')->where(['comp_id'=>$result['id']])->find();
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
        foreach($param as $i=>$j){
            $sss=self::addScoreRole($j);
            if(!empty($sss['a'])){
                $finance_log[$i]['comp_id']=$j['comp_id'];
                $finance_log[$i]['score']=$sss['a']['financing']['score'];
                $finance_log[$i]['score_source']=$sss['a']['financing']['remark'];
                $finance_log[$i]['department_type']='金融部数据';
                $finance_log[$i]['add_time']=date("Y-m-d H:i:s");
                $finance_log[$i]['key_name']=array_keys($sss['a'])[0];
                $finance_log[$i]['ip']=get_client_ip();
            }
        }
        Db::startTrans();
        try{
            Db::name('comp_finance')->insertAll($param);
            Db::name("comp_score_log")->insertAll($finance_log);
            //更新分数表
            $basic_score=[];
            foreach ($finance_log as $m=>$n){
                $fields="comp_id,total_score,id";
                $score_arr=Db::name('comp_score')->field($fields)->where(['comp_id'=>$n['comp_id']])->find();
                $basic_score['total_score'] =$score_arr['total_score']+substr($n['score'],1);
                $basic_score['finance_score'] =substr($n['score'],1);
                $basic_score['update_time'] =date("Y-m-d H:i:s");
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