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

        $this->assign('page',$page);
        $this->assign('scoreList',$app);
        return $this->fetch();
    }

    public function detail(){
        return $this->fetch();
    }
    /*
     * @function:执行毛利率排名加分
     * @date:20180821
     * @author：yangyh
     * */
    public function execRateAddScore(){
        if($this->request->isAjax()){
            $num=Db::name('comp_basic_finance')->order('gross_profit_rate desc')->select();
            $total=count($num);
            $mod     = $total % 10;
            $num_s   = intval($total / 10);
            $score_arr = array();
            for ( $i = 0 ; $i < 10; $i++ ) {
                $score_arr[] = ($i + 1) * $num_s;
            }
            foreach ($score_arr as $key => $value) {
                if ($key < $mod) {
                    $score_arr[$key] = $value + $key + 1;
                } else {
                    $score_arr[$key] = $value + $mod;
                }
            }
            $i=0;$score=0;
            foreach ($num as $k=>$v){
                if($k>=0 && $k<$score_arr[0]){
                    $score=10;
                }elseif ($k>=$score_arr[0] && $k<$score_arr[1]){
                    $score=9;
                }elseif ($k>=$score_arr[1] && $k<$score_arr[2]){
                    $score=8;
                }elseif ($k>=$score_arr[2] && $k<$score_arr[3]){
                    $score=7;
                }elseif ($k>=$score_arr[3] && $k<$score_arr[4]){
                    $score=6;
                }elseif ($k>=$score_arr[4] && $k<$score_arr[5]){
                    $score=5;
                }elseif ($k>=$score_arr[5] && $k<$score_arr[6]){
                    $score=4;
                }elseif ($k>=$score_arr[6] && $k<$score_arr[7]){
                    $score=3;
                }elseif ($k>=$score_arr[7] && $k<$score_arr[8]){
                    $score=2;
                }elseif ($k>=$score_arr[8] && $k<$score_arr[9]){
                    $score=1;
                }

                $app[$i]['comp_id']=$v['comp_id'];
                $app[$i]['score']=$score;
                $app[$i]['remark']="毛利率排名，加".$score.'分';
                $app[$i]['type']="毛利率";
                $app[$i]['account_time']=date("Y");
                $app[$i]['add_time']=date('Y-m-d H:i:s');
                $i+=1;
            }
            try{
                for($i=0;$i<count($app);$i++){
                    Db::name('score_total')->where('comp_id',$app[$i]['comp_id'])->insert($app[$i]);
                }
                ajaxmsg(['status'=>0,'msg'=>'统计成功']);
            }catch (Exception $e){
                ajaxmsg(['status'=>-1,'msg'=>$e->getMessage()]);
            }
        }
    }

    /*
     * @function:执行销售额排名加分
     * @date:20180821
     * @author：yangyh
     * */
    public function execSaleAddScore(){
        if($this->request->isAjax()){
            $num=Db::name('comp_basic_finance')->order('gross_profit_rate desc')->select();
            $a = ceil(count($num)/10);//前几名几个人  2
            $d = intval(count($num)/10);//前几名几个人  1
            $b = count($num)%10;//前几名，每组多少人         3
            $app=[];
            foreach ($num as  $i=> $v) {
                ++$i;
                if($i < $a*$b) {
                    $c = ceil($i/$a);
                }else {
                    $c = ceil(($i - $a*$b)/$d)+$b;
                }
                switch ($c) {
                    case 1:
                        $score =10;
                        break;
                    case 2:
                        $score =9;
                        break;
                    case 3:
                        $score =8;
                        break;
                    case 4:
                        $score =7;
                        break;
                    case 5:
                        $score =6;
                        break;
                    case 6:
                        $score =5;
                        break;
                    case 7:
                        $score =4;
                        break;
                    case 8:
                        $score =3;
                        break;
                    case 9:
                        $score =2;
                        break;
                    case 10:
                        $score =1;
                        break;
                }
                $app[$i]['comp_id']=$v['comp_id'];
                $app[$i]['score']=$score;
                $app[$i]['remark']="税收额排名第".$c."，加".$score.'分';
                $app[$i]['type']="毛利率";
                $app[$i]['account_time']=date("Y");
                $app[$i]['add_time']=date('Y-m-d H:i:s');
            }
            try{
                for($i=0;$i<count($app);$i++){
                    Db::name('score_total')->where('comp_id',$app[$i]['comp_id'])->insert($app[$i]);
                }
                ajaxmsg(['status'=>0,'msg'=>'统计成功']);
            }catch (Exception $e){
                ajaxmsg(['status'=>-1,'msg'=>$e->getMessage()]);
            }
        }
    }
    /*
     * @function:执行税收额排名加分
     * @date:20180821
     * @author：yangyh
     * */
    public function execTaxAddScore(){
        if($this->request->isAjax()){
            $num=Db::name('comp_basic_finance')->order('gross_profit_rate desc')->select();
            $a = ceil(count($num)/10);//前几名几个人  2
            $d = intval(count($num)/10);//前几名几个人  1
            $b = count($num)%10;//前几名，每组多少人         3
            $app=[];
            foreach ($num as  $i=> $v) {
                ++$i;
                if($i < $a*$b) {
                    $c = ceil($i/$a);
                }else {
                    $c = ceil(($i - $a*$b)/$d)+$b;
                }
                switch ($c) {
                    case 1:
                        $score =10;
                        break;
                    case 2:
                        $score =9;
                        break;
                    case 3:
                        $score =8;
                        break;
                    case 4:
                        $score =7;
                        break;
                    case 5:
                        $score =6;
                        break;
                    case 6:
                        $score =5;
                        break;
                    case 7:
                        $score =4;
                        break;
                    case 8:
                        $score =3;
                        break;
                    case 9:
                        $score =2;
                        break;
                    case 10:
                        $score =1;
                        break;
                }
                $app[$i]['comp_id']=$v['comp_id'];
                $app[$i]['score']=$score;
                $app[$i]['remark']="税收额排名第".$c."，加".$score.'分';
                $app[$i]['type']="毛利率";
                $app[$i]['account_time']=date("Y");
                $app[$i]['add_time']=date('Y-m-d H:i:s');
            }
            try{
                for($i=0;$i<count($app);$i++){
                    Db::name('score_total')->where('comp_id',$app[$i]['comp_id'])->insert($app[$i]);
                }
                ajaxmsg(['status'=>0,'msg'=>'统计成功']);
            }catch (Exception $e){
                ajaxmsg(['status'=>-1,'msg'=>$e->getMessage()]);
            }
        }
    }
}