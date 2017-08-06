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
class CompOperateController extends AdminBaseController
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
}