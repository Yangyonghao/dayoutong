<?php
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use cmf\lib\Upload;
use PHPExcel;
use PHPExcel_IOFactory;
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
              $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION) );
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
             foreach($excel_array as $k=>$v) {
                 $city[$k]['Id']    = $v[0];
                 $city[$k]['code']  = $v[1];
                 $city[$k]['path']  = $v[2];
                 $city[$k]['pcode'] = $v[3];
                 $city[$k]['name']  = $v[4];
             }
//             Db::name('city')->insertAll($city); //批量插入数据
        } else {
             echo $file->getError();
        }
    }


//    导入
    public function import11(){
        if(!empty($_FILES['file_stu']['name'])){
            $tmp_file = $_FILES['file_stu']['tmp_name'];            //临时文件名
            $file_types = explode('.',$_FILES['file_stu']['name']); //拆分文件名
            $file_type = $file_types [count ( $file_types ) - 1];   //文件类型
            /*判断是否为excel文件*/
            if($file_type == 'xls' || $file_type == 'xlsx'|| $file_type == 'csv'){    //  符合类型
                /*上传业务*/
                $upload=new Upload();
//                $upload = new \Think\Upload();
                $upload->maxSize   =     3145728 ;
                $upload->exts      =     array('xls', 'csv', 'xlsx');
                $upload->rootPath  =     './Public';
                $upload->savePath  =     '/Excel/';
                $upload->saveName  =     date('YmdHis');
                $info   =   $upload->upload();
                if(!$info) {    // 上传错误提示错误信息
                    $this->error($upload->getError());
                }else{  // 上传成功

                    //  读取文件
                    $filename='./Public'.$info['file_stu']['savepath'].$info['file_stu']['savename'];
                    import("Org.Yufan.ExcelReader");
                    vendor('PHPExcel.PHPExcel');
                    $reader = \PHPExcel_IOFactory::createReader('Excel2007'); //设置以Excel5格式(Excel97-2003工作簿)
                    $PHPExcel = $reader->load($filename); // 载入excel文件
                    $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
                    $highestRow = $sheet->getHighestRow(); // 取得总行数
                    var_dump($highestRow);
                    $highestColumm = $sheet->getHighestColumn(); // 取得总列数

                    /** 循环读取每个单元格的数据 */
                    $data = array();
                    for ($row = 2; $row <= $highestRow; $row++){//行数是以第1行开始

                        if($column = 'A'){
                            $data['name'] = $sheet->getCell($column.$row)->getValue();
                        }
                        if($column = 'B'){
                            $data['account'] = $sheet->getCell($column.$row)->getValue();
                        }
                        if($column = 'C'){
                            $data['password'] = $sheet->getCell($column.$row)->getValue();
                        }
                        M('data')->add($data);
                    }
                    $this->success('导入数据库成功',U('Excel/show'));
                }
            } else{ //  不符合类型业务
                $this->error('不是excel文件，请重新上传...');
            }
        }else{
            $this->error('(⊙o⊙)~没传数据就导入');
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