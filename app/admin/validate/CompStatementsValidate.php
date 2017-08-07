<?php
    /**
     * Created by PhpStorm.
     * User: YHx
     * Date: 2017/8/2
     * Time: 16:29
     */
namespace app\admin\validate;

use think\Validate;

class CompStatementsValidate extends Validate
{
    protected $rule = [
        'comp_id'             => 'require|gt:0',
        'monthly_sales'       => 'require|number',//月度销售额
        'monthly_tax_amount'  => 'require|number',//月税收额
        'comp_income_tax'     => 'require|number',//企业所得税
        'construction_tax'    => 'require|number',//城建税
        'personal_tax'        => 'require|number',//个人所得税
        'river_management_fee'=> 'require|number',//河道管理费
        'additional_edu_fees' => 'require|number',//教育附加费
        'local_edu_fees'      => 'require|number',//地方教育附加费
        'profit_current'      => 'require|number',//本期净利润
        'profit_year'         => 'require|number',//本年净利润
        'taxable_sales'       => 'require|number',//应税销售额
        'add_value_tax'       => 'require|number',//增值税
    ];
    protected $message = [
        'comp_id.gt' => '请选择企业名称',
        'monthly_sales.require'        => '月度销售额不为空',//月度销售额
        'monthly_sales.number'         => '月度销售仅为数字',//月度销售额
        'monthly_tax_amount.require'   => '月税收额不为空',//月税收额
        'monthly_tax_amount.number'    => '月税收额仅为数字',//月税收额
        'comp_income_tax.require'      => '月税收额不为空',//企业所得税
        'comp_income_tax.number'       => '月税收额仅为数字',//企业所得税
        'construction_tax.require'     => '城建税不为空',//城建税
        'construction_tax.number'      => '城建税仅为数字',//城建税
        'personal_tax.require'         => '个人所得税不为空',//个人所得税
        'personal_tax.number'          => '个人所得税仅为数字',//个人所得税
        'river_management_fee.require' => '河道管理费不为空',//河道管理费
        'river_management_fee.number'  => '河道管理费仅为数字',//河道管理费
        'additional_edu_fees.require'  => '教育附加费不为空',//教育附加费
        'additional_edu_fees.number'   => '教育附加费仅为数字',//教育附加费
        'local_edu_fees.require'       => '地方教育附加费不为空',//地方教育附加费
        'local_edu_fees.number'        => '地方教育附加费仅为数字',//地方教育附加费
        'profit_current.require'       => '本期净利润不为空',//本期净利润
        'profit_current.number'        => '本期净利润仅为数字',//本期净利润
        'profit_year.require'          => '本年净利润不为空',//本年净利润
        'profit_year.number'           => '本年净利润仅为数字',//本年净利润
        'taxable_sales.require'        => '应税销售额不为空',//应税销售额
        'taxable_sales.number'         => '应税销售额仅为数字',//应税销售额
        'add_value_tax.require'        => '增值税不为空',//增值税
        'add_value_tax.number'         => '增值税仅为数字',//增值税
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];

}
?>