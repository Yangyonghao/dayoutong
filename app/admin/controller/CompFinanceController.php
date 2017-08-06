<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/1
 * Time: 14:42
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;
use app\admin\model\CompFinanceModel;
use think\Db;

class CompFinanceController extends AdminBaseController
{
    public function index(){


        /**搜索条件**/
        $comp_name = trim($this->request->param('comp_name'));
        $where=[];
        if ($comp_name) {
            $where['comp_name'] = ['like', "%$comp_name%"];
        }

        $result_list=Db::name('comp_finance')
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

    /*
     * @添加页面
     * @author：yangyonghao
     * @date:2017-8-5
     * */
    public function add()
    {
        $comp_arr=Db::name('comp_basic')
            ->where('id','NOT IN',function($query){
                $query->name('comp_finance')->where('status',1)->field('comp_id');
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

            $compFinanceModel = new CompFinanceModel();
            $post=$this->request->param();
            $result = $this->validate($post, 'CompFinance');
            if ($result !== true) {
                $this->error($result);
            }
            $result = $compFinanceModel->addCompFinance($post);

            if ($result === false) {
                $this->error('添加失败!');
            }

            $this->success('添加成功!', url('CompFinance/index'));
        }
    }




}