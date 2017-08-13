<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/1
 * Time: 14:42
 */

namespace app\admin\controller;


use app\admin\model\CompBusinessModel;
use cmf\controller\AdminBaseController;
use think\Db;

class CompBusinessController extends AdminBaseController
{
    public function index(){


        /**搜索条件**/
        $comp_name = trim($this->request->param('comp_name'));
        $where=[];
        if ($comp_name) {
            $where['comp_name'] = ['like', "%$comp_name%"];
        }

        $result_list=Db::name('comp_business')
            ->alias('a')
            ->join('spec_comp_basic w','a.comp_id = w.id')->field('a.id as business_id,w.*,a.*')
            ->where($where)
            ->order("a.id DESC")->paginate(10);
        // 获取分页显示
        $page = $result_list->render();
        $this->assign('result_list',$result_list);
        $this->assign('page',$page);
        return $this->fetch();
    }

    /*
     * @添加页面
     * @author：yangyonghao
     * @date:2017-8-5
     * */
    public function add()
    {
        $comp_arr=Db::name('comp_basic')
            ->where('id','NOT IN',function($query){
                $query->name('comp_business')->where('status',1)->field('comp_id');
            })
            ->field('id,comp_name')->select();

//        $comp_arr=Db::name('comp_basic')->field('id,comp_name')->select();
        $this->assign('comp_arr',$comp_arr);
        return $this->fetch();
    }
    /*
     * @执行添加功能
     * @author：yangyonghao
     * @date:2017-8-5
     * */
    public function addPost(){
        if ($this->request->isPost()) {

            $compBusinessModel = new CompBusinessModel();
            $post=$this->request->param();
            $result = $this->validate($post, 'CompBusiness');
            if ($result !== true) {
                $this->error($result);
            }
            $result = $compBusinessModel->addCompBusiness($post);

            if ($result === false) {
                $this->error('添加失败!');
            }
            $this->success('添加成功!', url('CompBusiness/index'));
        }
    }

    public function edit(){
        $id=$this->request->param('id');
        $business_info=Db::name('comp_business')->where('id',$id)->find();
        $comp_arr=Db::name('comp_basic')->where('status',1)->field('id,comp_name')->select();
        $this->assign('business_info',$business_info);
        $this->assign('comp_arr',$comp_arr);

        return $this->fetch();
    }


    public function editPost(){
        if ($this->request->isPost()) {
            $post=$this->request->param();

            $admin_info=Db::name('comp_business')
                ->field('storage,logistics,oil_quality,collection,performance,transaction_num')
                ->where('id',$post['business_id'])->find();

            $compBusinessModel = new CompBusinessModel();

            $result = $this->validate($post, 'CompBusiness');
            if ($result !== true) {
                $this->error($result);
            }
            //获取减掉业务部分数的总分
            $comp_id=$post['comp_id'];
            $old_score=$this->getOldTotalScore($comp_id,'sales_score');

            $result = $compBusinessModel->editCompBusiness($post);

            if($result){
                //取差集
                $ssp=array_diff_assoc($post,$admin_info);
                unset($ssp["comp_id"]);unset($ssp["business_id"]);
                //获取字段相应的分数数组
                $result =  $this->getScoreRole($ssp,$admin_info);
                $i=0;
                foreach ($result as $key => $value){
                    $app[$i]['score']=$value["score"];
                    $app[$i]['score_source']=$value["remark"];
                    $app[$i]['comp_id']=$comp_id;
                    $app[$i]['department_type']='业务部数据';
                    $app[$i]['add_time']=date('Y-m-d H:i:s');
                    $app[$i]['key_name']=$key;
                    $app[$i]['ip']=get_client_ip();
                    Db::name('comp_score_log')->insert($app[$i]);
                    $i+=1;
                }
                $data=[
                    'comp_id'=>$comp_id,
                    'department_type'=>'业务部数据'
                ];
                $score=Db::name('comp_score_log')->where($data)->sum('score');
                $new_total_score=$score+$old_score;
                $comp_score=[
                    'comp_id'=>$comp_id,
                    'total_score'=>$new_total_score,
                    'sales_score'=>$score,
                ];
                Db::name('comp_score')->where('comp_id',$comp_id)->update($comp_score);
            }

            if ($result === false) {
                $this->error('更新失败!');
            }
            $this->success('保存成功!', url('CompBusiness/index'));
        }
    }

    //分数加法计算规则
    public function getScoreRole($data,$admin_info){
        $account_score =[];

        if(isset($data['storage'])){
            //是否有仓库
            $storage=$data['storage']=='是'?["remark" => "有仓库,加2分", "score" => "+2"]:["remark" => "没有仓库，减2分", "score" => "-2"];
            $account_score['storage']=$storage;
        }

        if(isset($data['logistics'])){
            //是否有物流
            $logistics=$data['logistics']=='是'?["remark" => "有物流,加2分", "score" => "+2"]:["remark" => "没有物流，减2分", "score" => "-2"];
            $account_score['logistics']=$logistics;
        }
        if(isset($data['oil_quality'])){
            //是否有货物质量问题
            $oil_quality=$data['oil_quality']=='否'?["remark" => "没有货物质量问题，加2分", "score" => "+2"]:["remark" => "有货物质量问题，减2分", "score" => "-2"];
            $account_score['oil_quality']=$oil_quality;
        }
        if(isset($data['collection'])){
            //回款周期，记2分
            $new_score=$this->getScoreNum('collection',$data['collection']);
            $old_score=$this->getScoreNum('collection',$admin_info['collection']);
            if($new_score>$old_score){
                $sco=$new_score-$old_score;
                $account_score['collection']=["remark" => "回款周期选择".$data['collection'].",加".$sco."分", "score" => "+".$sco];
            }else{
                $sco=$old_score-$new_score;
                $account_score['collection']=["remark" => "回款周期选择".$data['collection'].",减".$sco."分", "score" => "-".$sco];
            }

        }

        if(isset($data['transaction_num'])){
            //交易频次 54321
            $new_score=$this->getScoreNum('transaction_num',$data['transaction_num']);
            $old_score=$this->getScoreNum('transaction_num',$admin_info['transaction_num']);
            if($new_score>$old_score){
                $sco=$new_score-$old_score;
                $account_score['transaction_num']=["remark" => "交易频次选择".$data['transaction_num'].",加".$sco."分", "score" => "+".$sco];
            }else{
                $sco=$old_score-$new_score;
                $account_score['transaction_num']=["remark" => "交易频次选择".$data['transaction_num'].",减".$sco."分", "score" => "-".$sco];
            }

        }
        if(isset($data['performance'])){
            //请选择请选择 履约情况很好记4分，较好记3分，正常记2分，一般记1分
            $new_score=$this->getScoreNum('performance',$data['performance']);
            $old_score=$this->getScoreNum('performance',$admin_info['performance']);
            if($new_score>$old_score){
                $sco=$new_score-$old_score;
                $account_score['performance']=["remark" => "履约情况选择".$data['performance'].",加".$sco."分", "score" => "+".$sco];
            }else{
                $sco=$old_score-$new_score;
                $account_score['performance']=["remark" => "履约情况选择".$data['performance'].",减".$sco."分", "score" => "-".$sco];
            }
        }

        return $account_score;
    }
    //获取分数值
    public function getScoreNum($str,$value){
        $score=0;
        if($str=='collection'){
            if($value=='较好'){
                $score=5;
            }elseif ($value=='正常'){
                $score=3;
            }elseif ($value=='一般'){
                $score=1;
            }

        }elseif ($str=='transaction_num'){
            if($value=='频繁'){
                $score=5;
            }elseif ($value=='较频繁'){
                $score=4;
            }elseif ($value=='一般'){
                $score=3;
            }elseif ($value=='较少'){
                $score=2;
            }elseif ($value=='极少'){
                $score=1;
            }
        }elseif ($str=='performance'){
            if($value=='很好'){
                $score=4;
            }elseif ($value=='较好'){
                $score=3;
            }elseif ($value=='正常'){
                $score=2;
            }elseif ($value=='一般'){
                $score=1;
            }
        }

        return $score;
    }

}