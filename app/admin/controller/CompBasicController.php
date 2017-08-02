<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/1
 * Time: 13:12
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
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
            $result_arr=$this->request->param();
            $post   = $result_arr['post'];
            var_dump($result_arr);
            var_dump($post);die;
        }
    }
}