<?php
    /**
     * Created by PhpStorm.
     * User: YHx
     * Date: 2017/8/2
     * Time: 16:29
     */
namespace app\admin\validate;

use think\Validate;

class CompBasicValidate extends Validate
{
    protected $rule = [
        'comp_name' => 'require',
        'comp_classify' => 'require|chs',
        'reg_time' => 'require|date',
        'reg_money' => 'require',
        'legal_person' => 'require',
        'link_addr' => 'require',
        'business_license_pic' => 'require',
        'check_box' => 'require|array',
        'service_pay' => 'require',
    ];
    protected $message = [
        'comp_name.require' => '企业名称不能为空',
        'comp_classify.require' => '企业分类不能为空',
        'comp_classify.chs' => '企业分类不为空',
        'reg_time.require' => '成立时间不能为空',
        'reg_money.require' => '注册资本不能为空',
        'legal_person.require' => '法人代表不能为空',
        'business_license_pic.require' => '企业基础资质不能为空',
        'link_addr.require' => '企业联系地址不能为空',
        'check_box.require' => '企业附加资质不能为空',
        'service_pay.require' => '是否支付服务费不能为空',
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];
}
?>