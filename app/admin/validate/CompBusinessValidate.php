<?php
    /**
     * Created by PhpStorm.
     * User: YHx
     * Date: 2017/8/2
     * Time: 16:29
     */
namespace app\admin\validate;

use think\Validate;

class CompBusinessValidate extends Validate
{
    protected $rule = [
        'comp_id'=>'gt:0',
        'collection' => 'gt:0',
        'transaction_num' => 'gt:0',
        'performance' => 'gt:0',

    ];
    protected $message = [
        'comp_id.gt' => '请选择企业名称',
        'collection.gt' => '请选择回款周期',
        'transaction_num.gt' => '请选择交易频次',
        'performance.gt' => '请选择履约情况',
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];

}
?>