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

            $this->success('添加成功!', url('CompStatements/index'));
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

        foreach($result_list as $val){
            $app[]=$val;
        }
//        var_dump($app);die;
        // 获取分页显示
        $page = $result_list->render();
        $this->assign('result_list', $result_list);
        $this->assign('page', $page);
        return $this->fetch();
    }
}