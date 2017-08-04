<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/1
 * Time: 13:12
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use app\admin\model\CompBasicModel;
use think\Db;

/**
 * Class NavController 导航类别管理控制器
 * @package app\admin\controller
 */
class CompBasicController extends AdminBaseController
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
        $where = ["status" => 1];
        /**搜索条件**/
        $comp_name = trim($this->request->param('comp_name'));

        if ($comp_name) {
            $where['comp_name'] = ['like', "%$comp_name%"];
        }
        $result_list=Db::name('comp_basic')->where($where)->order("id DESC")->paginate(10);
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
        return $this->fetch();
    }

    public function addPost(){
        if ($this->request->isPost()) {
            $compBasicModel = new CompBasicModel();
            $post=$this->request->param();
            $result = $this->validate($post, 'CompBasic');
            if ($result !== true) {
                $this->error($result);
            }
            $result = $compBasicModel->addCompBasic($post);

            if ($result === false) {
                $this->error('添加失败!');
            }

            $this->success('添加成功!', url('AdminCategory/index'));
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