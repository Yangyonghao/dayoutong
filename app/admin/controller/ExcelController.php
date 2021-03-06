<?php
namespace app\admin\controller;
use app\admin\model\CompBasicModel;
use cmf\controller\AdminBaseController;
use cmf\lib\Upload;
use PHPExcel;
use PHPExcel_Reader_CSV;
use think\Db;
use think\Loader;

class ExcelController extends AdminBaseController{
    public function excelList(){
        $this->display();
    }
    public function import(){
          Loader::import('PHPExcel.Classes.PHPExcel');
          Loader::import('PHPExcel.Classes.PHPExcel.IOFactory.PHPExcel_IOFactory');
          Loader::import('PHPExcel.Classes.PHPExcel.Reader.Excel5');
          //获取表单上传文件
          $dir = ROOT_PATH . 'public' . DS . 'upload';
          $file = request()->file('file_stu');
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
              $city = [];
              $compBasicModel = new CompBasicModel();

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
              $result = $compBasicModel->excelAddCompBasic($city);
              return $excel_array;
//             Db::name('city')->insertAll($city); //批量插入数据
        } else {
             echo $file->getError();
        }
    }

    //导出
    public function export(){
        import("ORG.Yufan.Excel");
        $list = M('data')->select();
        if($list == null){
            $this->error('数据库信息为空...',__APP__.'/Admin/Excel/show');
        }else{
            $row=array();
            $row[0]=array('平台名称','帐号','密码');
            $i=1;
            foreach($list as $v){
                $row[$i]['name'] = $v['name'];
                $row[$i]['account'] = $v['account'];
                $row[$i]['password'] = $v['password'];
                $i++;
            }
            $xls = new \Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML(date('YmdHis'));
        }
    }
    public function show(){
        $m = M('data');
        $data = $m->select();
        $this->assign('data',$data);
        $this->display();
    }
    public function outExcel(){
        $path=dirname(__FILE__);//找到当前脚本所在的路径
        vendor("PHPExcel.PHPExcel.PHPExcel");
        vendor("PHPExcel.PHPExcel.Writer.Excel5");
        vendor("PHPExcel.PHPExcel.Writer.Excel2007");
        vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel=new PHPExcel();
        $objWriter=new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter=new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $aaa=Db::name('comp_basic')->select()->toArray();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ID编号')
            ->setCellValue('B1', '公司名称')
            ->setCellValue('C1', '企业法人');
        $aaa_num=count($aaa);
        for ($i=2;$i<=$aaa_num+1;$i++){
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $aaa[$i-2]['id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $aaa[$i-2]['comp_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $aaa[$i-2]['legal_person']);
        }
        $objPHPExcel->getActiveSheet()->setTitle('user');      //设置sheet的名称
        $objPHPExcel->setActiveSheetIndex(0);                   //设置sheet的起始位置
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');   //通过PHPExcel_IOFactory的写函数将上面数据写出来
        $PHPWriter = \PHPExcel_IOFactory::createWriter( $objPHPExcel,"Excel2007");
        header('Content-Disposition: attachment;filename="用户信息.xlsx"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件

//      <form action="{:url('CompAdministration/outExcel')}" enctype="multipart/form-data" method="post">
//         <input type="submit" value="导出">
//      </form>

    }
}