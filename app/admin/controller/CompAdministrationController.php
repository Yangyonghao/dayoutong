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
//        foreach ($result_list as $v){
//            $aa[]=$v;
//        }
//        var_dump($aa);die;

        $b=['is_website' => "是"
            ,'evil_network' => "否"
            ,'criminal_law' => 0
            ,'civil_law' => 2
            ,'bank_credit' => "否"
            ,'abnormal_operation' => "是"
            ,'illegal_dishonesty' => "否"
            ,'legal_disputes' => 0];

        $a=[
            'is_website' => "否",
            ' evil_network' => "否"
            ,'criminal_law' => 1
            ,'civil_law' => "否"
            ,'bank_credit' => "否"
            ,'abnormal_operation' => "0"
            ,'illegal_dishonesty' => "2"
            ,'legal_disputes' => "0"
            ,'admin_id' => "2"];
        $ssp=array_diff_assoc($a,$b);
        dump($ssp);die;

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
            $CompAdministrationModel = new CompAdministrationModel();
            $post=$this->request->param();
            $result = $this->validate($post, 'CompAdministration');
            if ($result !== true) {
                $this->error($result);
            }
            $admin_id=$this->request->param('admin_id');
            $admin_info=Db::name('comp_administration')->field('is_website,evil_network,criminal_law,civil_law,bank_credit,abnormal_operation,illegal_dishonesty,legal_disputes')->where('id',$admin_id)->find();
//            $result = $CompAdministrationModel->editCompAdministration($post);

            $ssp=array_diff_assoc($admin_info,$post);
            dump($ssp);
            if ($result === false) {
                $this->error('添加失败!');
            }

            $this->success('保存成功!', url('CompAdministration/index'));
        }
    }
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