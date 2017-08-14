<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/1
 * Time: 13:12
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use app\admin\model\CompStatementsModel;
use app\admin\model\CompBasicFinanceModel;
use think\Db;

/**
 * Class CompAdministrationController 财务部数据
 *
 * @package app\admin\controller
 */
class CompStatementsController extends AdminBaseController
{
    /**
     * 财务管理列表
     *
     */
    public function index()
    {
        /**搜索条件**/
        $comp_name = trim($this->request->param('comp_name'));
        $input_monthly = trim($this->request->param('input_monthly'));
        $where = [];
        if ($comp_name) {
            $where['comp_name'] = ['like', "%$comp_name%"];
        }
        if ($input_monthly) {
            $where['input_monthly'] = ['like', "%$input_monthly%"];
        }
        $fields='a.id as statement_id,w.comp_name,a.input_monthly,a.monthly_tax_amount,a.monthly_sales,a.add_value_tax';
        $result_list = Db::name('comp_statements')
            ->alias('a')
            ->join('spec_comp_basic w', 'a.comp_id = w.id')->field($fields)
            ->where($where)
            ->order("a.id DESC")->paginate(10);
        foreach ($result_list as $a) {
            $ff[] = $a;
        }
        // 获取分页显示
        $page = $result_list->render();
        $this->assign('result_list', $result_list);
        $this->assign('page', $page);
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
        //获取未添
        $comp_arr = Db::name('comp_basic')
            ->where('status', 1)->field('id,comp_name')->select();
        $this->assign('comp_arr', $comp_arr);
        return $this->fetch();
    }

    /*
     * @function:执行添加
     * @author：yangyh
     * @date:2017,8,6
     * */
    public function addPost()
    {
        if ($this->request->isPost()) {
            $CompStatementsModel = new CompStatementsModel();
            $post = $this->request->param();
            $result = $this->validate($post, 'CompStatements');
            if ($result !== true) {
                $this->error($result);
            }
            $result = $CompStatementsModel->addCompStatements($post);
            if ($result === false) {
                $this->error('添加失败!');
            }
            $this->success('添加成功!', url('CompStatements/index'));
        }
    }

    public function edit()
    {
        $statement_id = $this->request->param('statement_id');
        $statement_info = Db::name('comp_statements')->where('id', $statement_id)->find();
        //获取未添
        $comp_arr = Db::name('comp_basic')
            ->where('status', 1)->field('id,comp_name')->select();
//        var_dump($statement_info);die;
        $this->assign('comp_arr', $comp_arr);
        $this->assign('statement_info', $statement_info);
        return $this->fetch();
    }

    /*
     * 保存修改数据
     * @date:20170807
     * @author:yangyonghao
     * */
    public function editPost()
    {
        if ($this->request->isPost()) {
            $data_post = $this->request->param();
            $result = $this->validate($data_post, 'CompStatements');
            if ($result !== true) {
                $this->error($result);
            }
            $id = $data_post['id'];
            unset($data_post['id']);
            $result_id = Db::name('comp_statements')->where('id', $id)->update($data_post);
            if ($result_id) {
                $this->success('保存成功!', url('CompStatements/index'));
            } else {
                $this->error('未更新数据！');
            }
        }
    }

    /*
     * @author:yangyh
     * @date:20170808
     * @function:详情
     * */
    public function compDetail()
    {
        $statement_id = $this->request->param('statement_id');
        $comp_info = Db::name('comp_statements')
            ->alias('a')
            ->join('spec_comp_basic w', 'a.comp_id = w.id')->field('a.id as statement_id,a.*,w.*')
            ->where('a.id',$statement_id)
            ->find();
        $this->assign('comp_info',$comp_info);
        return $this->fetch();
    }
    /*
     * @date:2017.8.8
     * @author:yyh
     * @function:添加财务明细计分信息
     * */
    public function addBasic(){
        $comp_arr=Db::name('comp_basic')
            ->where('id','NOT IN',function($query){
                $query->name('comp_basic_finance')->where('status',1)->field('comp_id');
            })->field('id,comp_name')->select();
        $this->assign('comp_arr',$comp_arr);

        return $this->fetch();
    }
    /*
     * @date:2017.8.8
     * @author:yyh
     * @function:执行添加财务明细计分信息
     * */
    public function addBasicPost(){
        if ($this->request->isPost()) {

            $compFinanceModel = new CompBasicFinanceModel();
            $post=$this->request->param();
            $result = $this->validate($post, 'CompBasicFinance');
            if ($result !== true) {
                $this->error($result);
            }
            $result = $compFinanceModel->addCompBasicFinance($post);

            if ($result === false) {
                $this->error('添加失败!');
            }

            $this->success('添加成功!', url('CompStatements/basicFinanceList'));
        }
    }

    /*
     * @date:2017.8.8
     * @author:yyh
     * @function:执行添加财务明细计分信息
     * */
    public function basicFinanceList(){
        $comp_name = trim($this->request->param('comp_name'));
        $where = [];
        if ($comp_name) {
            $where['comp_name'] = ['like', "%$comp_name%"];
        }

        $fields='a.id as basic_finance_id,a.*,w.*';
        $result_list = Db::name('comp_basic_finance')
            ->alias('a')
            ->join('spec_comp_basic w', 'a.comp_id = w.id')->field($fields)
            ->where($where)
            ->order("a.id DESC")->paginate(10);

        // 获取分页显示
        $page = $result_list->render();
        $this->assign('result_list', $result_list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    /*
     * @function:编辑财务部基本信息
     * @author:yangyh
     * */
    public function editBasic(){
        $statement_id = $this->request->param('basic_finance_id');


        $basic_finance_info = Db::name('comp_basic_finance')->where('id', $statement_id)->find();
        //获取未添
        $comp_arr=Db::name('comp_basic')
            ->where('id','NOT IN',function($query){
                $query->name('comp_basic_finance')->where('status',1)->field('comp_id');
            })->field('id,comp_name')->select();
        $this->assign('comp_arr', $comp_arr);
        $this->assign('basic_finance_info', $basic_finance_info);
        return $this->fetch();
    }
    /*
     * @function:执行编辑财务部基本信息
     * @author:yangyh
     * */
    public function editBasicPost(){
        //获取参数
        $post=$this->request->param();

        $admin_info=Db::name('comp_basic_finance')
            ->field('agency_fee,invoice_version')
            ->where('id',$post['basic_finance_id'])->find();
        //实例化model
        $compBasicFinance = new CompBasicFinanceModel();
        //验证
        $result = $this->validate($post, 'CompBasicFinance');
        if ($result !== true) {
            $this->error($result);
        }
        //获取减掉业务部分数的总分
        $comp_id=$post['comp_id'];
        //获取减去财务部分数的总分数
        $old_score=$this->getOldTotalScore($comp_id,'account_score');
        //修改财务部记录
        $result = $compBasicFinance->editCompBasicFinance($post);

        if($result){
            //取差集
            $ssp=array_diff_assoc($post,$admin_info);
            unset($ssp["comp_id"]);unset($ssp["basic_finance_id"]);
            //获取字段相应的分数数组
            $result =  $this->getNewScore($ssp,$admin_info);
            if(is_array($result) && !empty($result)){
                $i=0;
                foreach ($result as $key => $value){
                    $app[$i]['score']=$value["score"];
                    $app[$i]['score_source']=$value["remark"];
                    $app[$i]['comp_id']=$comp_id;
                    $app[$i]['department_type']='财务部数据';
                    $app[$i]['add_time']=date('Y-m-d H:i:s');
                    $app[$i]['key_name']=$key;
                    $app[$i]['ip']=get_client_ip();
                    Db::name('comp_score_log')->insert($app[$i]);
                    $i+=1;
                }
                $data=[
                    'comp_id'=>$comp_id,
                    'department_type'=>'财务部数据'
                ];
                $score=Db::name('comp_score_log')->where($data)->sum('score');
                //减去财务部的分数总分数+新财务部分数
                $new_total_score=$score+$old_score;
                $comp_score=[
                    'comp_id'=>$comp_id,
                    'total_score'=>$new_total_score,
                    'sales_score'=>$score,
                ];
                //更新分数
                Db::name('comp_score')->where('comp_id',$comp_id)->update($comp_score);
            }
        }

        if ($result === false) {
            $this->error('更新失败!');
        }
        $this->success('保存成功!', url('CompStatements/basicFinanceList'));
    }
    /*
     * @function：返回分数数组
     * @date:20170814
     * @author：yyh
     * */
    public function getNewScore($data,$admin_info){
        //分数加法计算规则发票版本 万元版，十万版，百万版，千万元版
        $account_score =[];

        if(isset($data['invoice_version'])){
            $new_score=$this->getScoreNum($data['invoice_version']);
            $old_score=$this->getScoreNum($admin_info['invoice_version']);
            if($new_score>$old_score){
                $sco=$new_score-$old_score;
                $account_score['invoice_version']=["remark" => "发票版本选择".$data['invoice_version'].",加".$sco."分", "score" => "+".$sco];
            }else{
                $sco=$old_score-$new_score;
                $account_score['invoice_version']=["remark" => "发票版本选择".$data['invoice_version'].",减".$sco."分", "score" => "-".$sco];
            }
        }

        if(isset($data['agency_fee'])){
            //是否缴纳代理记账费
            $logistics=$data['agency_fee']=='是'?["remark" => "有物流,加2分", "score" => "+2"]:["remark" => "没有物流，减2分", "score" => "-2"];
            $account_score['agency_fee']=$logistics;
        }

        return $account_score;
    }
    //获取相应的分数
    public function getScoreNum($param){
        $score=0;
        switch ($param){
            case '万元版':
                $score=2;
                break;
            case '十万版':
                $score=3;
                break;
            case '百万版':
                $score=4;
                break;
            case '千万元版':
                $score=5;
                break;
        }
        return $score;
    }


}