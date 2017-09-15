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
use think\Exception;

//公司分数排名
class ScoreCensusController extends AdminBaseController
{
    public function index(){
        /**搜索条件**/
        $comp_name = trim($this->request->param('comp_name'));
        $where=['status'=>1];
        $search=[];
        if ($comp_name) {
            $where['comp_name'] = ['like', "%$comp_name%"];
            $search['comp_name'] = $comp_name;
        }
        //分数统计列表
        $result_list=Db::name('comp_score')
            ->alias('a')->field('a.id as score_id,a.*,w.comp_name')
            ->join('spec_comp_basic w','a.comp_id = w.id')
            ->where($where)
            ->order("a.total_score DESC")->paginate(10)->appends($search);
        //获取分页显示
        $page = $result_list->render();
        $this->assign('result_list',$result_list);
        $this->assign('page',$page);
        return $this->fetch();
    }

    //总分数记录表
    public function scoreList(){
        /**搜索条件**/
        $comp_name = trim($this->request->param('comp_name'));
        $account_time = trim($this->request->param('account_time'));
        $sort_list = trim($this->request->param('sort_list'));
        $where=[];
        $search=[];
        if ($comp_name) {
            $where['comp_name'] = ['like', "%$comp_name%"];
            $search['comp_name'] = $comp_name;
        }
        if ($account_time) {
            $where['account_time'] = ['eq', "$account_time"];
            $search['account_time'] = $account_time;
        }

        $subsql = Db::table('spec_total_score')->field('sum(score) as score,account_time,comp_id')->group('comp_id')->buildSql();
        $scoreList=Db::table('spec_comp_score')->alias('a')
            ->join([$subsql=> 'w'], 'a.comp_id = w.comp_id')
            ->join("spec_comp_basic scb","a.comp_id = scb.id")
            ->field('(a.total_score+w.score) as sum_score,a.total_score,a.comp_id,w.score,w.account_time,scb.comp_name')
            ->where($where)
            ->order("sum_score DESC")->paginate(10)->appends($search);
        //获取分页显示
        $page = $scoreList->render();

        $this->assign('sort_list',$sort_list);
        $this->assign('page',$page);
        $this->assign('scoreList',$scoreList);
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
            $rate_date=$this->request->param("rate_date");
            //查询2017年的毛利率是否已经排名
            $condition=[
                'account_time'=>$rate_date,
                'type'=>'毛利率'
            ];
            $add_score_list=Db::name("total_score")->where($condition)->select();
            if(count($add_score_list)>0){
                return json(['status'=>-1,'msg'=>$rate_date.'的毛利率已排名加分']);
            }
            //查询毛利率
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
                $app[$i]['account_time']=$rate_date;
                $app[$i]['add_time']=date('Y-m-d H:i:s');
                $i+=1;
            }
            try{
                $arg=[];
                for($j=0;$j<count($app);$j++){
                    $insert_id=Db::name('total_score')->insertGetId($app[$j]);
                    array_push($arg,$insert_id);
                }
                if(!empty($arg)){
                    return json(['status'=>0,'msg'=>'统计成功']);
                }else{
                    return json(['status'=>-1,'msg'=>'统计失败']);
                }
            }catch (Exception $e){
                return json(['status'=>-1,'msg'=>$e->getMessage()]);
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
            $sale_date=$this->request->param("sale_date");
            //判断是否已经添加销售额排名
            $condition=[
                'account_time'=>$sale_date,
                'type'=>'销售额'
            ];
            $add_score_list=Db::name("total_score")->where($condition)->select();
            if(count($add_score_list)>0){
                return json(['status'=>-1,'msg'=>$sale_date.'的销售额已排名加分']);
            }
            //查询每个公司十二个月的销售额，并倒序
            $new_year=$sale_date;
            $num=Db::query("select sum(monthly_sales) as total_sales,comp_id from spec_comp_statements where input_year=".$new_year." group by comp_id ORDER BY total_sales DESC");

            $a = ceil(count($num)/10);//前几名几个人  2
            $d = intval(count($num)/10);//前几名几个人  1
            $b = count($num)%10;//前几名，每组多少人         3
            $app=[];
            foreach ($num as  $i=> $v) {
                ++$i;
                if($i < $a*$b) {
                    $c = ceil($i/$a);
                }elseif($i > $b){
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
                $app[$i]['remark']="销售额排名第".$c."，加".$score.'分';
                $app[$i]['type']="销售额";
                $app[$i]['account_time']=$sale_date;
                $app[$i]['add_time']=date('Y-m-d H:i:s');
            }
            try{
                $arg=[];
                for($j=1;$j<=count($app);$j++){
                    $insert_id=Db::name('total_score')->insertGetId($app[$j]);
                    array_push($arg,$insert_id);
                }
                if(!empty($arg)){
                    return json(['status'=>0,'msg'=>'统计成功']);
                }else{
                    return json(['status'=>-1,'msg'=>'统计失败']);
                }
            }catch (Exception $e){
                return json(['status'=>-1,'msg'=>$e->getMessage()]);
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
            $tax_date=$this->request->param("tax_date");
            $condition=[
                'account_time'=>$tax_date,
                'type'=>'税收额'
            ];
            $add_score_list=Db::name("total_score")->where($condition)->select();
            if(count($add_score_list)>0){
                return json(['status'=>-1,'msg'=>$tax_date.'的税收额已排名加分']);
            }
            //查询每个公司十二个月的税收额，并倒序
            $new_year=$tax_date;
            $result=Db::query("select sum(monthly_tax_amount) as total_sales,comp_id from spec_comp_statements where input_year=".$new_year." group by comp_id ORDER BY total_sales DESC");

            $a = ceil(count($result)/10);//前几名几个人  2
            $d = intval(count($result)/10);//前几名几个人  1
            $b = count($result)%10;//前几名，每组多少人         3
            $app=[];
            foreach ($result as  $i=> $v) {
                ++$i;
                if($i < $a*$b) {
                    $c = ceil($i/$a);
                }else if($i > $b){
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
                $app[$i]['type']="税收额";
                $app[$i]['account_time']=$tax_date;
                $app[$i]['add_time']=date('Y-m-d H:i:s');
            }
            try{
                $arg=[];
                for($j=1;$j<=count($app);$j++){
                    $insert_id=Db::name('total_score')->insertGetId($app[$j]);
                    array_push($arg,$insert_id);
                }
                if(!empty($arg)){
                    return json(['status'=>0,'msg'=>'统计成功']);
                }else{
                    return json(['status'=>-1,'msg'=>'统计失败']);
                }
            }catch (Exception $e){
                return json(['status'=>-1,'msg'=>$e->getMessage()]);
            }
        }
    }

    //分数日志表
    public function scoreLog(){
        $where=[];
        $comp_name=$this->request->param("comp_name");
        $department_type=$this->request->param("department_type");
        $search=[];
        if($comp_name){
            $where['comp_name'] = ['like', "%$comp_name%"];
            $search['comp_name'] = $comp_name;
        }
        if($department_type){
            $where['department_type'] = ['like', "%$department_type%"];
            $search['department_type'] = $department_type;
        }
        $score_log_list=Db::name("comp_score_log")->alias('a')
            ->join("spec_comp_basic scb","scb.id=a.comp_id")
            ->field("a.*,scb.comp_name")
            ->where($where)->order("add_time DESC")->paginate(20)->appends($search);
        //获取分页显示
        $page = $score_log_list->render();
        $this->assign("page",$page);
        $this->assign("score_log_list",$score_log_list);
        return $this->fetch();
    }

    //总分数日志表
    public function totalScoreLog(){
        $where=[];
        $comp_name=$this->request->param("comp_name");
        $type=$this->request->param("type");
        $search=[];
        if($comp_name){
            $where['comp_name'] = ['like', "%$comp_name%"];
            $search['comp_name'] = $comp_name;
        }
        if($type){
            $where['type'] = ['like', "%$type%"];
            $search['type'] = $type;
        }
        $score_log_arr=Db::name("total_score")->alias('a')
            ->join("spec_comp_basic scb","scb.id=a.comp_id")
            ->field("a.*,scb.comp_name")
            ->where($where)->order("add_time DESC")->paginate(10)->appends($search);
        //获取分页显示
        $page = $score_log_arr->render();
        $this->assign("page",$page);
        $this->assign("score_log_arr",$score_log_arr);
        return $this->fetch();
    }


    /*
     * 统计公司所有信息
     * @author:yyh
     * @date:20170915
     * */
    public function compList(){
        $where = ["status" => 1];
        /**搜索条件**/
        $comp_name = trim($this->request->param('comp_name'));
        $search=[];
        if ($comp_name) {
            $where['comp_name'] = ['like', "%$comp_name%"];
            $search['comp_name'] =$comp_name;
        }
        $result_list=Db::name('comp_basic')->where($where)->order("id DESC")->paginate(10)->appends($search);
        // 获取分页显示
        $page = $result_list->render();
        $this->assign('result_list',$result_list);
        $this->assign('page',$page);
        return $this->fetch();
    }

    //公司所有信息
    public function compDetail(){
        $comp_id=$this->request->param('comp_id');

        //会员部数据
        $spec_comp_fields="comp_name,comp_classify,reg_money,
                     business_license_pic,link_addr,legal_person,
                     service_pay,reg_time,comp_aptitude";
        $spec_comp=Db::name('comp_basic')->field($spec_comp_fields)->where('id',$comp_id)->find();

        //行政部数据
        $spec_comp_admin_fields="abnormal_operation,bank_credit,illegal_dishonesty,
                     legal_disputes,civil_law,criminal_law,
                     is_website,evil_network";
        $spec_comp_administration=Db::table('spec_comp_administration')->field($spec_comp_admin_fields)->where('comp_id',$comp_id)->find();

        //财务部数据
        $spec_comp_basic_finance=Db::table('spec_comp_basic_finance')->field("agency_fee,gross_profit_rate,invoice_version")->where('comp_id',$comp_id)->find();
        //业务部数据
        $spec_comp_business=Db::table('spec_comp_business')->field("`storage`,logistics,collection,oil_quality,transaction_num,performance")->where('comp_id',$comp_id)->find();
        //金融部数据
        $spec_comp_finance=Db::table('spec_comp_finance')->field("financing")->where('comp_id',$comp_id)->find();

        $spec_comp  =   empty($spec_comp)?[]:$spec_comp;
        $spec_comp_admin=empty($spec_comp_administration)?[]:$spec_comp_administration;
        $spec_comp_business=empty($spec_comp_business)?[]:$spec_comp_business;
        $spec_comp_finance=empty($spec_comp_finance)?[]:$spec_comp_finance;
        $spec_comp_basic_finance=empty($spec_comp_basic_finance)?[]:$spec_comp_basic_finance;
        //合并数组
        $score_log_arr=array_merge($spec_comp,$spec_comp_admin,$spec_comp_business,$spec_comp_finance,$spec_comp_basic_finance);
//        dump($score_log_arr);die;
        $this->assign('comp_info',$score_log_arr);
        return $this->fetch();
    }

}