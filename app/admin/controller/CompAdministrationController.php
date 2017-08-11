<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/1
 * Time: 13:12
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use app\admin\model\CompAdministrationModel;
use think\Db;
use think\Exception;

/**
 * Class CompAdministrationController 行政部管理控制器
 *
 * @package app\admin\controller
 */
class CompAdministrationController extends AdminBaseController
{
    /**
     * 导航管理
     * @adminMenu(
     *     'name'   => '导航管理',
     *     'parent' => 'admin/Setting/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 30,
     *     'icon'   => '',
     *     'remark' => '导航管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        /**搜索条件**/
        $comp_name = trim($this->request->param('comp_name'));
        $where=[];
        if ($comp_name) {
            $where['comp_name'] = ['like', "%$comp_name%"];
        }

        $result_list=Db::name('comp_administration')
            ->alias('a')
            ->join('spec_comp_basic w','a.comp_id = w.id')->field('a.id as admin_id,a.*,w.comp_name,w.id')
            ->where($where)
            ->order("a.id DESC")->paginate(10);
        // 获取分页显示
        $page = $result_list->render();
        $this->assign('result_list',$result_list);
        $this->assign('page',$page);
        return $this->fetch();

    }

    /**
     * 添加导航
     * @adminMenu(
     *     'name'   => '添加导航',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加导航',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $comp_arr=Db::name('comp_basic')
            ->where('id','NOT IN',function($query){
                $query->name('comp_administration')->where('status',1)->field('comp_id');
            })
            ->field('id,comp_name')->select();

        $this->assign('comp_arr',$comp_arr);
        return $this->fetch();
    }
    /*
     * @function:执行添加
     * @author：yangyh
     * @date:2017,8,6
     * */
    public function addPost(){
        if ($this->request->isPost()) {
            $CompAdministrationModel = new CompAdministrationModel();
            $post=$this->request->param();
            $result = $this->validate($post, 'CompAdministration');
            if ($result !== true) {
                $this->error($result);
            }
            $result = $CompAdministrationModel->addCompAdministration($post);

            if ($result === false) {
                $this->error('添加失败!');
            }

            $this->success('添加成功!', url('CompAdministration/index'));
        }
    }
    /*
     * @function：编辑页面
     * */
    public function edit(){
        $admin_id = $this->request->param('id');
        $comp_admin_info = Db::name('comp_administration')->where('id', $admin_id)->find();
        //获取未添
        $comp_arr = Db::name('comp_basic')
            ->where('status', 1)->field('id,comp_name')->select();
        $this->assign('comp_arr', $comp_arr);
        $this->assign('comp_admin_info', $comp_admin_info);
        return $this->fetch();
    }
    /*
     * */
    public function editPost(){
        if ($this->request->isPost()) {
            $admin_id=$this->request->param('admin_id');
            $admin_info=Db::name('comp_administration')->field('legal_disputes,civil_law,criminal_law,is_website,evil_network,bank_credit,abnormal_operation,illegal_dishonesty')->where('id',$admin_id)->find();

            $CompAdministrationModel = new CompAdministrationModel();
            $post=$this->request->param();
            //获取减去行政部分数的总分
            $comp_id=$post['comp_id'];
            $old_score=$this->getOldTotalScore($comp_id);
            $result = $this->validate($post, 'CompAdministration');
            if ($result !== true) {
                $this->error($result);
            }
            $result_info=$CompAdministrationModel->editCompAdministration($post);
            if($result_info){
                //取差集
                $ssp=array_diff_assoc($post,$admin_info);

                $comp_id    =   $ssp["comp_id"];
                unset($ssp["comp_id"]);unset($ssp["admin_id"]);
                //获取字段相应的分数数组
                $result =  $this->getScoreRole($ssp,$admin_info);
                $i=0;
                foreach ($result as $key => $value){
                    $app[$i]['score']=$value["score"];
                    $app[$i]['score_source']=$value["remark"];
                    $app[$i]['comp_id']=$comp_id;
                    $app[$i]['department_type']='行政部数据';
                    $app[$i]['add_time']=date('Y-m-d H:i:s');
                    $app[$i]['key_name']=$key;
                    $app[$i]['ip']=get_client_ip();
                    Db::name('comp_score_log')->insert($app[$i]);
                    $i+=1;
                }
                $data=[
                    'comp_id'=>$comp_id,
                    'department_type'=>'行政部数据'
                ];
                $score=Db::name('comp_score_log')->where($data)->sum('score');
                $new_total_score=$score+$old_score;
                $comp_score=[
                    'comp_id'=>$comp_id,
                    'total_score'=>$new_total_score,
                    'admin_score'=>$score,
                ];
                Db::name('comp_score')->where('comp_id',$comp_id)->update($comp_score);

                $this->success('保存成功!', url('CompAdministration/index'));
            }else{
                $this->error('保存失败!');
            }
        }
    }


    public function lostScore($data){
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
        if(isset($data['bank_credit']) && $data['bank_credit']=='是'){
            $account_score['bank_credit']=["remark" => "选择是记-3分", "score" => "-3"];
        }elseif(isset($data['abnormal_operation']) && $data['abnormal_operation']=='是'){
            $account_score['abnormal_operation']=["remark" => "选择是记2分", "score" => "-2"];
        }elseif(isset($data['illegal_dishonesty']) && $data['illegal_dishonesty']=='是'){
            //否被列入严重违法失信企业名单 选择否，记2分
            $account_score['illegal_dishonesty']=["remark" => "选择否记-2分", "score" => "-2"];
        }elseif(isset($data['legal_disputes']) && $data['legal_disputes']>0){
            //企业民事法律纠纷次数0次记2分
            $account_score['legal_disputes']=["remark" => "纠纷次数大于0记-2分", "score" => "-2"];
        }elseif(isset($data['civil_law']) && $data['civil_law']>0){
            //股东、法人、高管民事法律纠纷次数
            $account_score['civil_law']=["remark" => "纠纷次数大于0记-2分", "score" => "-2"];
        }elseif(isset($data['criminal_law']) && $data['criminal_law']>0){
            //股东、法人、高管刑事法律纠纷次数选择否，记2分
            $account_score['criminal_law']=["remark" => "纠纷次数大于0记-2分", "score" => "-2"];
        }elseif(isset($data['is_website']) && $data['is_website']=='否'){
            //是否有公司官网选择是， 记1分
            $account_score['is_website']=["remark" => "选择是记-1分", "score" => "-1"];
        }elseif(isset($data['evil_network']) && $data['evil_network']=='否'){
            //是否有网络搜索恶评选择是记1分
            $account_score['evil_network']=["remark" => "选择是记-1分", "score" => "-1"];
        }
        return $account_score;
    }


    //分数加法计算规则
    public function getScoreRole($data,$old_info){
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
        if(isset($data['bank_credit'])){
            $bank_credit = $data['bank_credit']=='否' ? ["remark" => "没有被银行列入不诚信名单，加3分", "score" => "+3"] : ["remark" => "被银行列入不诚信名单，减3分", "score" => "-3"];
            $account_score['bank_credit']  =    $bank_credit;
        }

        if(isset($data['abnormal_operation'])){
            //是否被列入经营异常名录
            $abnormal_operation=$data['abnormal_operation']=='否'?["remark" => "没有被列入经营异常名录，加2分", "score" => "+2"]:["remark" => "被列入经营异常名录，减2分", "score" => "-2"];
            $account_score['abnormal_operation']=$abnormal_operation;
        }

        if(isset($data['illegal_dishonesty'])) {
            //否被列入严重违法失信企业名单 选择否，记2分
            $illegal_dishonesty=$data['illegal_dishonesty'] == '否'?["remark" => "没有被列入严重违法失信企业名单，加2分", "score" => "+2"]:["remark" => "被列入严重违法失信企业名单，减2分", "score" => "-2"];
            $account_score['illegal_dishonesty']=$illegal_dishonesty;
        }
        if(isset($data['is_website'])) {
            //是否有公司官网选择是， 记1分
            $is_website=$data['is_website'] == '是'?["remark" => "有公司官网，加1分", "score" => "+1"]:["remark" => "没有有公司官网，减1分", "score" => "-1"];
            $account_score['is_website'] =$is_website ;
        }

        if(isset($data['evil_network'])) {
            //是否有网络搜索恶评选择是记1分
            $evil_network=$data['evil_network'] == '否'?["remark" => "没有网络搜索恶评，加1分", "score" => "+1"]:["remark" => "有网络搜索恶评减1分", "score" => "-1"];
            $account_score['evil_network']=$evil_network;
        }

        //企业民事法律纠纷次数0次记2分
        if(isset($data['legal_disputes'])) {
            if($old_info['legal_disputes']==0 && $data['legal_disputes'] >0 ){
                $account_score['legal_disputes']=["remark" => "企业民事法律纠纷".$data['legal_disputes']."次，减2分", "score" => "-2"];
            }elseif($old_info['legal_disputes'] >0 && $data['legal_disputes']==0){
                $account_score['legal_disputes']=["remark" => "企业民事法律纠纷".$data['legal_disputes']."次，加2分", "score" => "+2"];
            }
        }

        //股东、法人、高管民事法律纠纷次数
        if(isset($data['civil_law'])) {
            if($old_info['civil_law'] ==0 && $data['civil_law']>0){
                $account_score['civil_law']=["remark" => "股东、法人、高管民事法律纠纷".$data['civil_law']."次，减2分", "score" => "-2"];
            }elseif($old_info['civil_law'] >0 && $data['civil_law']==0){
                $account_score['civil_law']=["remark" => "股东、法人、高管民事法律纠纷".$data['civil_law']."次，加2分", "score" => "+2"];
            }
        }

        //股东、法人、高管刑事法律纠纷次数选择否，记2分
        if(isset($data['criminal_law'])) {
            if($old_info['criminal_law'] ==0 && $data['criminal_law']>0){
                $account_score['criminal_law']=["remark" => "股东、法人、高管刑事法律纠纷".$data['criminal_law']."次，减2分", "score" => "-2"];
            }elseif($old_info['criminal_law'] >0 && $data['criminal_law']==0){
                $account_score['criminal_law']=["remark" => "股东、法人、高管刑事法律纠纷".$data['criminal_law']."次，加2分", "score" => "+2"];
            }
        }
        return $account_score;
    }

    //获取总分数
    public function getOldTotalScore($comp_id){
        $data_arr=['comp_id'=>$comp_id];
        $score_detail=Db::name('comp_score')->field('total_score,admin_score')->where($data_arr)->find();
        $total_score=$score_detail['total_score']-$score_detail['admin_score'];
        return $total_score;
    }
}