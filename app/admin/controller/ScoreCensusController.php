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
        $subsql = Db::table('spec_total_score')->where(['account_time'=>'2017'])->field('sum(score) as score,account_time,comp_id')->group('comp_id')->buildSql();
        $scoreList=Db::table('spec_comp_score')->alias('a')->join([$subsql=> 'w'], 'a.comp_id = w.comp_id')->field('a.total_score,a.comp_id,w.score,w.account_time')->order("a.total_score DESC")->paginate(10);
        //获取分页显示
        $page = $scoreList->render();
        $app=[];
        foreach ($scoreList as $k=>$val){
            $data=['status'=>1,'id'=>$val['comp_id']];
            $result=$this->getProjectInfo('comp_basic',$data);
            $val['comp_name']=isset($result['comp_name'])?$result['comp_name']:'--';
            $val['sum_score']=$val['score']+$val['total_score'];
            $app[]=$val;
        }

//        $sql="SELECT scs.total_score,a.*
//              FROM spec_comp_score AS scs
//                LEFT JOIN (
//                    SELECT
//                        SUM(score) AS score,
//                        account_time,
//                        comp_id
//                    FROM
//                        spec_total_score
//                    WHERE
//                        account_time = '2017'
//                    GROUP BY
//                        comp_id
//              ) AS a ON scs.comp_id = a.comp_id";
//        $scoreList=Db::query($sql);
//        dump($result);die;
        //分数统计列表
//        $scoreList=Db::name('comp_score')
//            ->alias('a')->field('a.id as score_id,a.*,w.comp_name')
//            ->join('spec_comp_basic w','a.comp_id = w.id')
//            ->join('spec_comp_basic w','a.comp_id = w.id')
//            ->order("a.total_score DESC")->paginate(10);
//        dump($app);die;
//        $this->assign('scoreList',$scoreList);
        $this->assign('page',$page);
        $this->assign('scoreList',$app);
        return $this->fetch();
    }
}