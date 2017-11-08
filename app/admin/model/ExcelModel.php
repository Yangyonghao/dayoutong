<?php
/**
 * Created by PhpStorm.
 * User: YHx
 * Date: 2017/8/3
 * Time: 10:48
 */

namespace app\admin\model;
use PHPExcel;
use PHPExcel_Reader_CSV;
use think\Db;
use think\Model;
use think\Loader;

class ExcelModel extends Model
{
    public function import($file,$type){
        Loader::import('PHPExcel.Classes.PHPExcel');
        Loader::import('PHPExcel.Classes.PHPExcel.IOFactory.PHPExcel_IOFactory');
        Loader::import('PHPExcel.Classes.PHPExcel.Reader.Excel5');
        //获取表单上传文件
        $dir = ROOT_PATH . 'public' . DS . 'upload';
//        $file = request()->file('file_stu');
        $info = $file->validate(['size'=>3145728,'ext'=>'xls,xlsx,csv'])->rule('uniqid')->move($dir) ;//上传验证后缀名,以及上传之后移动的地址
        if($info) {
            $filename = $dir. DS .$info->getSaveName();
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if($extension == 'xlsx' ) {
                $objReader =\PHPExcel_IOFactory::createReader('Excel2007');
                $objPHPExcel = $objReader->load($filename, $encode = 'utf-8');
            }else if($extension == 'xls'){
                $objReader =\PHPExcel_IOFactory::createReader('Excel5');
                $objPHPExcel = $objReader->load($filename, $encode = 'utf-8');
            }else if($extension=='csv'){
                $PHPReader = new PHPExcel_Reader_CSV();
                //默认输入字符集
                $PHPReader->setInputEncoding('GBK');
                //默认的分隔符
                $PHPReader->setDelimiter(',');
                //载入文件
                $objPHPExcel = $PHPReader->load($filename);
            }
            $excel_array=$objPHPExcel->getsheet(0)->toArray();   //转换为数组格式
            array_shift($excel_array);  //删除第一个数组(标题);
            $comp_data_arr=self::importType($type,$excel_array);
            return $comp_data_arr;
        } else {
            echo $file->getError();
        }
    }

    public function importType($comp_type,$excel_array){
        $city=[];
        if($comp_type=='会员部数据'){
            foreach($excel_array as $k=>$v) {
                $city[$k]['comp_name']      = $v[0];//公司名称
                $city[$k]['comp_classify']  = $v[1];//公司类型
                $city[$k]['reg_time']       = $v[2];//注册时间
                $city[$k]['reg_money']      = $v[3];//注册资金
                $city[$k]['link_addr']      = $v[4];//联系地址
                $city[$k]['legal_person']   = $v[5];//企业法人
                $city[$k]['service_pay']    = $v[6];//是否支付服务费
                $city[$k]['comp_aptitude']  = $v[7];//企业附加资质
            }
        }else if($comp_type=='金融部数据'){
            foreach($excel_array as $k=>$v) {
                $city[$k]['comp_id']      = $v[0];//公司名称
                $city[$k]['financing']    = $v[1];//公司类型
            }
        }else if($comp_type=='业务部数据'){
            foreach($excel_array as $k=>$v) {
                $city[$k]['comp_id']         = $v[0];//公司ID
                $city[$k]['storage']         = $v[1];//长期合作的油库
                $city[$k]['logistics']       = $v[2];//长期合作的物流公司
                $city[$k]['collection']      = $v[3];//回款周期,'较好','一般','正常'
                $city[$k]['oil_quality']     = $v[4];//是否有货物质量问题，是或者否
                $city[$k]['transaction_num'] = $v[5];//交易频次'频繁','较频繁','一般','较少','极少'
                $city[$k]['performance']     = $v[6];//履约情况
            }
        }else if($comp_type=='行政部数据'){
            foreach($excel_array as $k=>$v) {
                $city[$k]['comp_id']            = $v[0];//公司名称
                $city[$k]['bank_credit']        = $v[1];//是否被银行列入不诚信名单，是或者否
                $city[$k]['abnormal_operation'] = $v[2];//是否被列入经营异常名录
                $city[$k]['illegal_dishonesty'] = $v[3];//否被列入严重违法失信企业名单
                $city[$k]['legal_disputes']     = $v[4];//企业民事法律纠纷次数
                $city[$k]['civil_law']          = $v[5];//股东、法人、高管民事法律纠纷次数
                $city[$k]['criminal_law']       = $v[6];//股东、法人、高管刑事法律纠纷次数
                $city[$k]['is_website']         = $v[7];//是否有公司官网
                $city[$k]['evil_network']       = $v[8];//是否有网络搜索恶评
            }
        }else if($comp_type=='财务部数据'){
            foreach($excel_array as $k=>$v) {
                $city[$k]['comp_name']      = $v[0];//公司名称
                $city[$k]['comp_classify']  = $v[1];//公司类型
                $city[$k]['reg_time']       = $v[2];//注册时间
                $city[$k]['reg_money']      = $v[3];//注册资金
                $city[$k]['link_addr']      = $v[4];//联系地址
                $city[$k]['legal_person']   = $v[5];//企业法人
                $city[$k]['service_pay']    = $v[6];//是否支付服务费
                $city[$k]['comp_aptitude']  = $v[7];//企业附加资质
            }
        }

        return $city;
    }
}