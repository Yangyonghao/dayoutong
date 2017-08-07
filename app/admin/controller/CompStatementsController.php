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
        $where=[];
        if($comp_name) {
            $where['comp_name'] = ['like', "%$comp_name%"];
        }
        if($input_monthly) {
            $where['input_monthly'] = ['like', "%$input_monthly%"];
        }

        $result_list=Db::name('comp_statements')
            ->alias('a')
            ->join('spec_comp_basic w','a.comp_id = w.id')
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
        //获取未添
        $comp_arr=Db::name('comp_basic')
            ->where('status',1)->field('id,comp_name')->select();

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
            $CompStatementsModel = new CompStatementsModel();
            $post=$this->request->param();
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

    public function scoreRole($artitude_score_count){
        $account_score = array(
            "comp_name"            => array("remark" => "添加名称获取1积分","score" => "1"),
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