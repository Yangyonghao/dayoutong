<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/15
 * Time: 10:19
 */

namespace app\admin\controller;
use think\Db;
use cmf\controller\AdminBaseController;

//公司分数排名
class ScoreCensusController extends AdminBaseController
{
    public function index(){
        /**搜索条件**/
        $comp_name = trim($this->request->param('comp_name'));
        $where=['status'=>1];
        if ($comp_name) {
            $where['comp_name'] = ['like', "%$comp_name%"];
        }
        //分数统计列表
        $result_list=Db::name('comp_score')
            ->alias('a')->field('a.id as score_id,a.*,w.comp_name')
            ->join('spec_comp_basic w','a.comp_id = w.id')
            ->where($where)
            ->order("a.total_score DESC")->paginate(10);
        //获取分页显示
        $page = $result_list->render();
        $this->assign('result_list',$result_list);
        $this->assign('page',$page);
        return $this->fetch();
    }

    //总分数记录表
    public function scoreList(){
        $sql="SELECT scs.total_score,a.*
              FROM spec_comp_score AS scs
                LEFT JOIN (
                    SELECT
                        SUM(score) AS score,
                        account_time,
                        comp_id
                    FROM
                        spec_total_score
                    WHERE
                        account_time = '2017'
                    GROUP BY
                        comp_id
              ) AS a ON scs.comp_id = a.comp_id";
        $scoreList=Db::query($sql);
//        dump($result);die;


        //分数统计列表
//        $scoreList=Db::name('comp_score')
//            ->alias('a')->field('a.id as score_id,a.*,w.comp_name')
//            ->join('spec_comp_basic w','a.comp_id = w.id')
//            ->join('spec_comp_basic w','a.comp_id = w.id')
//            ->order("a.total_score DESC")->paginate(10);
        $this->assign('scoreList',$scoreList);
        return $this->fetch();
    }
}