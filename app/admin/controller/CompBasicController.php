<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/1
 * Time: 13:12
 */
namespace app\admin\controller;

use app\admin\model\ExcelModel;
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

            $this->success('添加成功!', url('CompBasic/index'));
        }
    }
    //编辑
    public function edit(){
        $id=$this->request->param('id');
        $list=[
            '危化证','成品油经营资质','进出口贸易证'
        ];
        $param=['id'=>$id];
        $basic_info=$this->getProjectInfo('comp_basic',$param);
        $basic_info['comp_aptitude']=explode('|',$basic_info['comp_aptitude']);
        $this->assign('list',$list);
        $this->assign('basic_info',$basic_info);
        return $this->fetch();
    }
    /*
     * @function:执行编辑
     * @author:201708014
     * @author:yyh
     * */
    public function editPost(){
        if ($this->request->isPost()) {
            //接收参数
            $post=$this->request->param();
            $compBasicModel = new CompBasicModel();
            //字段验证是否为空
            $result = $this->validate($post, 'CompBasic');
            $post['comp_aptitude']=rtrim(implode('|',$post['check_box']),'|');
            $comp_id=$post['basic_id'];

            if ($result !== true) {
                $this->error($result);
            }
            //修改
            $basic_info=Db::name('comp_basic')
                ->field('comp_aptitude,service_pay')
                ->where('id',$post['basic_id'])->find();
            //获取减去会员部分数的总分数
            $old_score=$this->getOldTotalScore($comp_id,'member_score');
            $result = $compBasicModel->editCompBasic($post);
            if($result){
                //取差集
                $ssp=array_diff_assoc($post,$basic_info);
                unset($ssp["comp_id"]);unset($ssp["basic_finance_id"]);
                //获取字段相应的分数数组
                $result =  $this->getNewScore($ssp,$basic_info);
                if(is_array($result) && !empty($result)){
                    $i=0;
                    foreach ($result as $key => $value){
                        $app[$i]['score']=$value["score"];
                        $app[$i]['score_source']=$value["remark"];
                        $app[$i]['comp_id']=$comp_id;
                        $app[$i]['department_type']='会员部数据';
                        $app[$i]['add_time']=date('Y-m-d H:i:s');
                        $app[$i]['key_name']=$key;
                        $app[$i]['ip']=get_client_ip();
                        Db::name('comp_score_log')->insert($app[$i]);
                        $i+=1;
                    }
                    $data=[
                        'comp_id'=>$comp_id,
                        'department_type'=>'会员部数据'
                    ];
                    $score=Db::name('comp_score_log')->where($data)->sum('score');
                    //减去财务部的分数总分数+新财务部分数
                    $new_total_score=$score+$old_score;
                    $comp_score=[
                        'comp_id'=>$comp_id,
                        'total_score'=>$new_total_score,
                        'member_score'=>$score,
                    ];
                    //更新分数
                    Db::name('comp_score')->where('comp_id',$comp_id)->update($comp_score);
                }
            }
            if ($result === false) {
                $this->error('保存失败!');
            }

            $this->success('保存成功!', url('CompBasic/index'));
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
            'comp_aptitude'        => array("remark" => '添加附加资质,加'.$artitude_score_count.'分',"score" => '+'.$artitude_score_count),
        );
        return $account_score;
    }

    /*
     * @function：返回分数数组
     * @date:20170814
     * @author：yyh
     * */
    public function getNewScore($data,$admin_info){
        //分数加法计算规则发票版本 万元版，十万版，百万版，千万元版
        $account_score =[];

        if(isset($data['comp_aptitude'])){
            $new_score=count(explode('|',$data['comp_aptitude']));
            $old_score=count(explode('|',$admin_info['comp_aptitude']));;
            if($new_score>$old_score){
                $sco=$new_score-$old_score;
                $account_score['comp_aptitude']=["remark" => "企业附加资质选择".$data['comp_aptitude'].",加".$sco."分", "score" => "+".$sco];
            }else{
                $sco=$old_score-$new_score;
                $account_score['comp_aptitude']=["remark" => "企业附加资质选择".$data['comp_aptitude'].",减".$sco."分", "score" => "-".$sco];
            }
        }

        if(isset($data['service_pay'])){
            //是否缴纳代理记账费
            $logistics=$data['service_pay']=='是'?["remark" => "支付服务费,加5分", "score" => "+5"]:["remark" => "没有支付服务费，减5分", "score" => "-5"];
            $account_score['service_pay']=$logistics;
        }

        return $account_score;
    }

    /*
     * @author:yangyh
     * @date:20171108
     * 导入会员数据
     * */
    public function import(){
        $file = request()->file('file_stu');
        $excel=new ExcelModel();
        $comp_basic=new CompBasicModel();
        $basic=$excel->import($file,'会员部数据');
        if(!$basic){
            $this->success('请检查导入的数据是否存在问题!', url('CompBasic/index'));
        }
        $result=$comp_basic->excelAddCompBasic($basic);
        if(!$result){
            $this->success('请检查导入的数据是否存在问题!', url('CompBasic/index'));
        }else{
            $this->success('导入成功!', url('CompBasic/index'));
        }
    }
}