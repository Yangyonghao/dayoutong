<?php
    /**
     * Created by PhpStorm.
     * User: YHx
     * Date: 2017/8/2
     * Time: 16:29
     */
namespace app\admin\validate;

use think\Validate;

class CompBasicFinanceValidate extends Validate
{
    protected $rule = [
        'comp_id'             => 'require|gt:0',
        'gross_profit_rate'       => 'require',//月度销售额
    ];
    protected $message = [
        'comp_id.gt' => '请选择企业名称',
        'gross_profit_rate.require'        => '毛利率不为空',//毛利率
//        'monthly_sales.number'         => '月度销售仅为数字',//月度销售额
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];

}
?>